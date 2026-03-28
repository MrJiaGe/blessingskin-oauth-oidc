<?php

namespace Blessing\OAuth\OIDC;

use App\Models\User;
use Blessing\Filter;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Vectorface\Whip\Whip;

class OIDCAuthController extends Controller
{
    /**
     * 处理 OIDC 回调，基于 sub 绑定用户并同步信息
     *
     * @param Dispatcher $dispatcher
     * @param Filter $filter
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Dispatcher $dispatcher, Filter $filter)
    {
        Log::info('OIDC Callback: 开始处理', [
            'url' => request()->fullUrl(),
            'has_code' => request()->has('code'),
            'has_state' => request()->has('state'),
        ]);

        try {
            $remoteUser = Socialite::driver('oidc')->user();
            Log::info('OIDC Callback: 获取用户信息成功', [
                'id' => $remoteUser->id ?? null,
                'email' => $remoteUser->email ?? null,
                'nickname' => $remoteUser->nickname ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('OIDC Callback: 认证失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'OIDC authentication failed: ' . $e->getMessage());
        }

        $sub = $remoteUser->id;
        $email = $remoteUser->email;
        $issuer = env('OIDC_ISSUER');

        if (empty($sub)) {
            Log::error('OIDC Callback: 未获取到 sub claim');
            abort(500, 'OIDC Provider did not provide subject (sub) claim.');
        }

        $binding = OIDCUserBinding::findBySub($sub, $issuer);
        Log::info('OIDC Callback: 查找绑定结果', ['binding_found' => !is_null($binding)]);

        $user = null;
        $isNewUser = false;

        if ($binding) {
            $user = User::where('uid', $binding->uid)->first();
            if (!$user) {
                Log::warning('OIDC Callback: 绑定存在但用户不存在，删除绑定', ['uid' => $binding->uid]);
                $binding->delete();
                $binding = null;
            }
        }

        if (!$user) {
            if (empty($email)) {
                Log::error('OIDC Callback: 未获取到邮箱且无绑定记录');
                abort(500, 'OIDC Provider did not provide email. Cannot create new user without binding.');
            }

            $user = User::where('email', $email)->first();
            Log::info('OIDC Callback: 通过邮箱查找用户', ['user_found' => !is_null($user)]);

            if (!$user) {
                $isNewUser = true;
                $whip = new Whip();
                $ip = $whip->getValidIpAddress();
                $ip = $filter->apply('client_ip', $ip);

                $user = new User();
                $user->email = $email;
                $user->score = option('user_initial_score');
                $user->avatar = 0;
                $user->password = '';
                $user->ip = $ip;
                $user->permission = User::NORMAL;
                $user->register_at = Carbon::now();
                $user->last_sign_at = Carbon::now()->subDay();
                $user->verified = true;
                $user->save();

                Log::info('OIDC Callback: 创建新用户', ['uid' => $user->uid, 'email' => $email]);
                $dispatcher->dispatch('auth.registration.completed', [$user]);
            }

            OIDCUserBinding::bindUser($user->uid, $sub, $issuer);
            Log::info('OIDC Callback: 创建绑定', ['uid' => $user->uid, 'sub' => $sub]);
        }

        $nickname = $remoteUser->nickname ?? $remoteUser->name;
        if (!empty($nickname)) {
            $user->nickname = $nickname;
        }

        if (!empty($email)) {
            $user->email = $email;
        }

        $user->save();

        $dispatcher->dispatch('auth.login.ready', [$user]);
        Auth::login($user);
        $dispatcher->dispatch('auth.login.succeeded', [$user]);

        Log::info('OIDC Callback: 登录成功', ['uid' => $user->uid]);

        return redirect('/user');
    }
}
