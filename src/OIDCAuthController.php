<?php

namespace Blessing\OAuth\OIDC;

use App\Models\User;
use Blessing\Filter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class OIDCAuthController extends Controller
{
    /** Resolve returning users by identity and start the first-time linking flow. */
    public function callback(Dispatcher $dispatcher, Filter $filter, OIDCAccountLink $accounts)
    {
        OIDCSession::clear();

        try {
            $remoteUser = Socialite::driver('oidc')->user();
        } catch (InvalidStateException $e) {
            return $this->loginError('session_expired');
        } catch (\Exception $e) {
            Log::warning('OIDC callback failed', ['exception_type' => get_class($e)]);

            return $this->loginError('authentication_failed');
        }

        $sub = $remoteUser->id;
        if (!is_string($sub) || $sub === '' || strlen($sub) > 255) {
            return $this->loginError('authentication_failed');
        }

        $nickname = $remoteUser->nickname ?? $remoteUser->name ?? '';
        $identity = [
            'sub' => $sub,
            'issuer' => env('OIDC_ISSUER') ?: null,
            'email' => is_string($remoteUser->email) ? $remoteUser->email : null,
            'email_verified' => ($remoteUser->user['email_verified'] ?? false) === true,
            'nickname' => is_string($nickname) ? $nickname : '',
        ];

        try {
            $binding = OIDCUserBinding::findBySub($sub, $identity['issuer']);
        } catch (ValidationException $e) {
            return redirect('/auth/login')->withErrors($e->errors());
        }

        if ($binding) {
            $user = User::where('uid', $binding->uid)->first();
            if (!$user) {
                return $this->loginError('user_not_found');
            }

            $nickname = $identity['nickname'];
            if ($nickname !== '') {
                $user->nickname = mb_substr($nickname, 0, 50);
                $user->save();
            }

            return $this->login($user, $dispatcher);
        }

        OIDCSession::storePending($identity);
        if (env('OIDC_LINK_ENABLED', true)) {
            return redirect()->route('oidc.link.choice');
        }

        // Keep the direct flow when its email checks pass. Otherwise offer manual linking.
        try {
            if (OIDCAccountLink::usersWithEmail($identity['email'])->exists()) {
                $user = $accounts->autoLink($identity);
            } else {
                $user = $accounts->register($identity, $filter);
                $dispatcher->dispatch('auth.registration.completed', [$user]);
            }
        } catch (ValidationException $e) {
            return redirect()->route('oidc.link.choice')->withErrors($e->errors());
        }

        OIDCSession::clear();

        return $this->login($user, $dispatcher);
    }

    private function login(User $user, Dispatcher $dispatcher)
    {
        $dispatcher->dispatch('auth.login.ready', [$user]);
        Auth::login($user);
        $dispatcher->dispatch('auth.login.succeeded', [$user]);

        return redirect('/user');
    }

    private function loginError(string $key)
    {
        return redirect('/auth/login')->withErrors(['oidc' => trans('Blessing\OAuth\OIDC::general.' . $key)]);
    }
}
