<?php

namespace Blessing\OAuth\OIDC;

use App\Models\User;
use Blessing\Filter;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Vectorface\Whip\Whip;

/**
 * OIDC 账号关联控制器
 * 
 * 处理用户在 OIDC 登录时的账号关联选择
 */
class OIDCLinkController extends Controller
{
    /**
     * 显示账号关联选择页面
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showChoice()
    {
        $pending = OIDCSession::getPending();

        Log::debug('OIDC Link Choice: 检查 Session', [
            'has_pending' => !is_null($pending),
            'session_id' => Session::getId(),
        ]);

        if (!$pending) {
            Log::warning('OIDC Link Choice: Session 已过期或不存在');
            return redirect('/auth/login')
                ->withErrors(['oidc' => trans('Blessing\OAuth\OIDC::general.session_expired')]);
        }

        $email = $pending['email'];
        $emailExists = !empty($email) && User::where('email', $email)->exists();

        $token = OIDCSession::getToken();
        $loginUrl = url('/auth/login?token=' . $token);

        Log::debug('OIDC Link Choice: Token 和 URL', [
            'token' => substr($token, 0, 8) . '...',
            'login_url' => $loginUrl,
            'email_exists' => $emailExists,
        ]);

        return view('Blessing\OAuth\OIDC::link-choice', [
            'email' => $email,
            'nickname' => $pending['nickname'],
            'oidc_name' => env('OIDC_DISPLAY_NAME', 'OIDC'),
            'emailExists' => $emailExists,
            'login_url' => $loginUrl,
        ]);
    }

    /**
     * 创建新账户
     *
     * @param Dispatcher $dispatcher
     * @param Filter $filter
     * @return \Illuminate\Http\RedirectResponse
     */
public function createNewAccount(Dispatcher $dispatcher, Filter $filter)
    {
        $pending = OIDCSession::getPending();

        Log::debug('OIDC Link Create: 检查 Session', [
            'has_pending' => !is_null($pending),
        ]);

        if (!$pending) {
            Log::warning('OIDC Link Create: Session 已过期或不存在');
            return redirect('/auth/login')
                ->withErrors(['oidc' => trans('Blessing\OAuth\OIDC::general.session_expired')]);
        }

        $email = $pending['email'];
        $sub = $pending['sub'];
        $issuer = $pending['issuer'] ?? null;
        $nickname = $pending['nickname'];
        $avatar = $pending['avatar'] ?? null;

        if (empty($email)) {
            OIDCSession::clear();
            Log::error('OIDC Link Create: OIDC Provider 未提供邮箱');
            return redirect('/auth/login')
                ->withErrors(['oidc' => 'OIDC Provider did not provide email.']);
        }

        // 安全拦截：如果邮箱已存在，不应走到这里，退回选择页
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            Log::warning('OIDC Link Create: 邮箱已存在，退回选择页', [
                'uid' => $existingUser->uid,
                'email' => $email,
            ]);
            OIDCSession::clear();
            return redirect('/auth/login')
                ->withErrors(['oidc' => '该邮箱已注册，请使用「登录并关联」']);
        }

        $whip = new Whip();
        $ip = $whip->getValidIpAddress();
        $ip = $filter->apply('client_ip', $ip);

        $user = new User();
        $user->email = $email;
        $user->nickname = !empty($nickname) ? $nickname : explode('@', $email)[0];
        $user->score = option('user_initial_score');
        $user->avatar = 0;
        $user->password = '';
        $user->ip = $ip;
        $user->permission = User::NORMAL;
        $user->register_at = Carbon::now();
        $user->last_sign_at = Carbon::now()->subDay();
        $user->verified = true;
        $user->save();

        Log::info('OIDC Link Create: 创建新用户成功', [
            'uid' => $user->uid,
            'email' => $email,
        ]);

        OIDCUserBinding::bindUser($user->uid, $sub, $issuer);
        Log::info('OIDC Link Create: 绑定创建完成', [
            'uid' => $user->uid,
            'sub' => $sub,
        ]);

        $dispatcher->dispatch('auth.registration.completed', [$user]);

        OIDCSession::clear();
        Session::forget('oidc_pending_token');

        Auth::login($user);
        $dispatcher->dispatch('auth.login.succeeded', [$user]);

        return redirect('/user')
            ->with('success', trans('Blessing\OAuth\OIDC::general.account_created'))
            ->with('hint', trans('Blessing\OAuth\OIDC::general.password_hint'));
    }

    /**
     * 自动绑定并登录（邮箱已存在时使用）
     * 无需用户输入密码，直接绑定 OIDC sub 到该邮箱对应的用户并登录
     *
     * @param Dispatcher $dispatcher
     * @return \Illuminate\Http\RedirectResponse
     */
    public function autoLink(Dispatcher $dispatcher)
    {
        $pending = OIDCSession::getPending();

        if (!$pending) {
            Log::warning('OIDC Auto Link: Session 已过期');
            return redirect('/auth/login')
                ->withErrors(['oidc' => trans('Blessing\OAuth\OIDC::general.session_expired')]);
        }

        $email = $pending['email'];
        $sub = $pending['sub'];
        $issuer = $pending['issuer'] ?? null;

        $user = User::where('email', $email)->first();
        if (!$user) {
            Log::error('OIDC Auto Link: 邮箱对应的用户不存在', ['email' => $email]);
            OIDCSession::clear();
            return redirect('/auth/login')
                ->withErrors(['oidc' => trans('Blessing\OAuth\OIDC::general.user_not_found')]);
        }

        OIDCUserBinding::bindUser($user->uid, $sub, $issuer);
        OIDCSession::clear();
        Session::forget('oidc_pending_token');

        Auth::login($user);
        $dispatcher->dispatch('auth.login.succeeded', [$user]);

        Log::info('OIDC Auto Link: 自动绑定并登录成功', [
            'uid' => $user->uid,
            'sub' => $sub,
        ]);

        return redirect('/user')
            ->with('success', trans('Blessing\OAuth\OIDC::general.link_success'));
    }

    /**
     * 完成现有账户关联
     * 用户已登录 BlessingSkin，将 OIDC 账号绑定到当前用户
     *
     * @param Dispatcher $dispatcher
     * @return \Illuminate\Http\RedirectResponse
     */
    public function completeLink(Dispatcher $dispatcher)
    {
        $token = request()->get('token');
        $pending = null;

        if ($token) {
            $pending = OIDCSession::getPendingByToken($token);
            Log::debug('OIDC Link Complete: 从 URL Token 获取数据', [
                'token' => substr($token, 0, 8) . '...',
                'has_pending' => !is_null($pending),
            ]);
        }

        if (!$pending) {
            $pending = OIDCSession::getPending();
            Log::debug('OIDC Link Complete: 从 Session 获取数据', [
                'has_pending' => !is_null($pending),
                'session_id' => Session::getId(),
                'session_all_keys' => array_keys(Session::all()),
            ]);
        }

        if (!$pending) {
            Log::warning('OIDC Link Complete: 无法获取 OIDC 待关联数据', [
                'token_provided' => !empty($token),
                'session_id' => Session::getId(),
            ]);
            return redirect('/user')
                ->withErrors(['oidc' => trans('Blessing\OAuth\OIDC::general.session_expired')]);
        }

        $user = Auth::user();
        if (!$user) {
            Log::warning('OIDC Link Complete: 用户未登录');
            return redirect('/auth/login')
                ->withErrors(['oidc' => trans('Blessing\OAuth\OIDC::general.please_login_first')]);
        }

        $sub = $pending['sub'];
        $issuer = $pending['issuer'];
        $nickname = $pending['nickname'];

        $existingBinding = OIDCUserBinding::where('uid', $user->uid)->first();
        if ($existingBinding) {
            if ($token) {
                OIDCSession::clearByToken($token);
            } else {
                OIDCSession::clear();
            }
            Log::warning('OIDC Link Complete: 用户已绑定其他 OIDC 账号', [
                'uid' => $user->uid,
                'existing_binding_id' => $existingBinding->id,
            ]);
            return redirect('/user')
                ->withErrors(['oidc' => trans('Blessing\OAuth\OIDC::general.already_linked')]);
        }

        $oidcBinding = OIDCUserBinding::findBySub($sub, $issuer);
        if ($oidcBinding) {
            if ($token) {
                OIDCSession::clearByToken($token);
            } else {
                OIDCSession::clear();
            }
            Log::warning('OIDC Link Complete: OIDC 账号已被其他用户绑定', [
                'sub' => $sub,
                'existing_binding_uid' => $oidcBinding->uid,
            ]);
            return redirect('/user')
                ->withErrors(['oidc' => trans('Blessing\OAuth\OIDC::general.oidc_already_bound')]);
        }

        Log::info('OIDC Link Complete: 开始创建绑定', [
            'uid' => $user->uid,
            'sub' => $sub,
            'issuer' => $issuer,
        ]);

        OIDCUserBinding::bindUser($user->uid, $sub, $issuer);

        if (!empty($nickname) && empty($user->nickname)) {
            $user->nickname = $nickname;
            $user->save();
        }

        if ($token) {
            OIDCSession::clearByToken($token);
        } else {
            OIDCSession::clear();
        }

        Log::info('OIDC Link Complete: 关联现有账户成功', [
            'uid' => $user->uid,
            'sub' => $sub,
        ]);

        Session::forget('oidc_pending_token');

        $dispatcher->dispatch('auth.login.succeeded', [$user]);

        return redirect('/user')
            ->with('success', trans('Blessing\OAuth\OIDC::general.link_success'));
    }

    /**
     * 检查是否有待处理的 OIDC 关联
     * 用于前端 JavaScript 自动跳转
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPending()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['has_pending' => false]);
        }

        $token = Session::get('oidc_pending_token');

        if (!$token) {
            return response()->json(['has_pending' => false]);
        }

        $pending = OIDCSession::getPendingByToken($token);

        if (!$pending) {
            Session::forget('oidc_pending_token');
            return response()->json(['has_pending' => false]);
        }

        Log::debug('OIDC Check Pending: 发现待处理关联', [
            'user_id' => $user->uid,
            'token' => substr($token, 0, 8) . '...',
        ]);

        return response()->json([
            'has_pending' => true,
            'token' => $token,
        ]);
    }
}
