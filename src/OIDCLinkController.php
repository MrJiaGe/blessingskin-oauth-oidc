<?php

namespace Blessing\OAuth\OIDC;

use Blessing\Filter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OIDCLinkController extends Controller
{
    public function showChoice()
    {
        $identity = OIDCSession::getPending();
        if (!$identity) {
            return $this->expired('/auth/login');
        }

        $matches = OIDCAccountLink::usersWithEmail($identity['email'])->get();
        $providerEmailVerified = OIDCAccountLink::emailIsVerified($identity);

        return view('Blessing\OAuth\OIDC::link-choice', [
            'email' => $identity['email'],
            'nickname' => $identity['nickname'],
            'oidc_name' => env('OIDC_DISPLAY_NAME', 'OIDC'),
            'canAutoLink' => OIDCAccountLink::canAutoLink($identity, $matches),
            'hasExistingBinding' => $providerEmailVerified && $matches->count() === 1
                && OIDCUserBinding::where('uid', $matches->first()->uid)->exists(),
            'canCreate' => $matches->isEmpty() && $providerEmailVerified,
            'providerEmailVerified' => $providerEmailVerified,
            'flowId' => $identity['flow_id'],
            'login_url' => url('/auth/login'),
        ]);
    }

    public function createNewAccount(Request $request, Dispatcher $dispatcher, Filter $filter, OIDCAccountLink $accounts)
    {
        $identity = OIDCSession::getPending();
        if (!$identity || !OIDCSession::matchesForm($identity, $request->input('flow_id'))) {
            return $this->expired('/auth/login');
        }

        try {
            $user = $accounts->register($identity, $filter);
        } catch (ValidationException $e) {
            return redirect()->route('oidc.link.choice')->withErrors($e->errors());
        }

        OIDCSession::clear();
        $dispatcher->dispatch('auth.registration.completed', [$user]);
        $dispatcher->dispatch('auth.login.ready', [$user]);
        Auth::login($user);
        $dispatcher->dispatch('auth.login.succeeded', [$user]);

        return redirect('/user')
            ->with('success', trans('Blessing\OAuth\OIDC::general.account_created'))
            ->with('hint', trans('Blessing\OAuth\OIDC::general.password_hint'));
    }

    public function autoLink(Request $request, Dispatcher $dispatcher, OIDCAccountLink $accounts)
    {
        $identity = OIDCSession::getPending();
        if (!$identity || !OIDCSession::matchesForm($identity, $request->input('flow_id'))) {
            return $this->expired('/auth/login');
        }

        try {
            $user = $accounts->autoLink($identity);
        } catch (ValidationException $e) {
            return redirect()->route('oidc.link.choice')->withErrors($e->errors());
        }

        OIDCSession::clear();
        $dispatcher->dispatch('auth.login.ready', [$user]);
        Auth::login($user);
        $dispatcher->dispatch('auth.login.succeeded', [$user]);

        return $this->linked();
    }

    public function showConfirmation(OIDCAccountLink $accounts)
    {
        $identity = OIDCSession::getPending();
        if (!$identity) {
            return $this->expired('/user');
        }

        $user = Auth::user();
        $bindings = OIDCUserBinding::where('uid', $user->uid)->limit(2)->get();
        $existing = $bindings->first();

        return view('Blessing\OAuth\OIDC::link-confirm', [
            'flowId' => $identity['flow_id'],
            'localUid' => $user->uid,
            'localEmail' => $user->email,
            'providerEmail' => $identity['email'],
            'localVerified' => $user->verified,
            'existingBinding' => $existing,
            'canReplaceBinding' => $accounts->canReplaceBinding($bindings, $identity),
            'canChangeEmail' => $accounts->canChangeEmail($user, $identity),
        ]);
    }

    public function completeLink(Request $request, OIDCAccountLink $accounts)
    {
        $identity = OIDCSession::getPending();
        if (!$identity || !OIDCSession::matchesForm($identity, $request->input('flow_id'))) {
            return $this->expired('/user');
        }

        $uid = Auth::user()->uid;
        if ((string) $uid !== $request->input('local_uid')) {
            return redirect()->route('oidc.link.confirm')->withErrors([
                'oidc' => trans('Blessing\OAuth\OIDC::general.confirmation_changed'),
            ]);
        }

        try {
            $accounts->linkExisting($uid, $identity, [
                'local_email' => $request->input('local_email'),
                'binding_id' => $request->input('binding_id') ?? '',
                'old_sub' => $request->input('old_sub'),
                'replace_binding' => $request->input('replace_binding') === '1',
                'change_email' => $request->input('change_email') === '1',
            ]);
        } catch (ValidationException $e) {
            return redirect()->route('oidc.link.confirm')->withErrors($e->errors());
        }

        OIDCSession::clear();

        return $this->linked();
    }

    private function expired(string $path)
    {
        return redirect($path)->withErrors(['oidc' => trans('Blessing\OAuth\OIDC::general.session_expired')]);
    }

    private function linked()
    {
        return redirect('/user')->with('success', trans('Blessing\OAuth\OIDC::general.link_success'));
    }
}
