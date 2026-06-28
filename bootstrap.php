<?php

// ── 插件入口：注册 Provider、监听事件、注册路由 ──

use App\Events\UserLoggedIn;
use App\Services\Hook;
use Blessing\Filter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Manager\ServiceProvider as SocialiteServiceProvider;
use Blessing\OAuth\OIDC\OIDCExtendSocialite;
use Blessing\OAuth\OIDC\OIDCProvider;
use Blessing\OAuth\OIDC\OIDCSession;
use Illuminate\Support\Facades\Log;

return function (Dispatcher $events, Filter $filter, $plugin) {
    // 注册自定义视图命名空间（views 目录结构不合规时的备选方案）
    View::addNamespace('Blessing\OAuth\OIDC', __DIR__.'/resources/views');

    // 从 .env 注入 OIDC 配置到 Laravel 的 services 数组
    config(['services.oidc' => [
        'client_id'     => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect'      => env('OIDC_REDIRECT_URI'),

        'authorize_url' => env('OIDC_AUTHORIZE_URL'),
        'token_url'     => env('OIDC_TOKEN_URL'),
        'userinfo_url'  => env('OIDC_USERINFO_URL'),
    ]]);

    // 确保 SocialiteProviders ServiceProvider 已注册
    try {
        app()->register(SocialiteServiceProvider::class);
    } catch (\Exception $e) {
        // 已注册则静默跳过
    }

    // 通过事件 + 手动 extend 双重注册 OIDCProvider
    $events->listen(SocialiteWasCalled::class, [OIDCExtendSocialite::class, 'handle']);

    Socialite::extend('oidc', function () {
        $config = config('services.oidc');
        $provider = new OIDCProvider(
            request(),
            $config['client_id'] ?? '',
            $config['client_secret'] ?? '',
            $config['redirect'] ?? ''
        );
        return $provider;
    });

    // ── Filter：登录页面捕获 token，用于关联流程 ──
    $filter->add('auth_page_rows:login', function ($rows) {
        $token = request()->get('token');
        if ($token) {
            $pending = OIDCSession::getPendingByToken($token);
            if ($pending) {
                Session::put('oidc_pending_token', $token);
                Session::save();

                Log::debug('OIDC: 登录页面捕获 pending token', [
                    'token' => substr($token, 0, 8) . '...',
                ]);
            }
        }
        return $rows;
    });

    // ── Event：用户登录后检测是否有待关联的 OIDC ──
    $events->listen(UserLoggedIn::class, function ($event) {
        $user = $event->user;

        Log::debug('OIDC Session: UserLoggedIn 事件触发', [
            'user_id' => $user->uid ?? null,
        ]);

        $token = Session::get('oidc_pending_token');

        if ($token) {
            $pending = OIDCSession::getPendingByToken($token);
            if ($pending) {
                // 利用 last_requested_path 让 BlessingSkin 登录后自动跳转
                Session::put('last_requested_path', url('/oidc/link/complete?token=' . $token));

                Log::info('OIDC: 用户登录成功，设置重定向到关联页面', [
                    'user_id' => $user->uid,
                    'token' => substr($token, 0, 8) . '...',
                ]);
            }
        }

        // 登录后 Session 会 regenerate，从 Cache 恢复数据
        OIDCSession::restoreAfterLogin();
    });

    // ── Filter：关联流程中隐藏登录页的 OIDC 按钮 ──
    $filter->add('oauth_providers', function (Collection $providers) {
        if (request()->has('token')) {
            return collect();  // 关联流程中只显示普通登录表单
        }

        $providers->put('oidc', [
            'icon' => 'fa-user-circle',
            'displayName' => env('OIDC_DISPLAY_NAME', 'OIDC'),
        ]);
        return $providers;
    });

    // 注册前端 JS（检测待关联状态，自动跳转）
    Hook::addScriptFileToPage($plugin->assets('link-redirect.js'), ['user']);

    // ── 路由注册 ──
    Hook::addRoute(function ($router) {
        // OIDC 回调（IDP 授权后跳转至此）
        $router->middleware(['web', 'guest'])
            ->get('oidc/callback', 'Blessing\OAuth\OIDC\OIDCAuthController@callback');

        // 账号关联选择页（首次登录时展示）
        $router->middleware(['web', 'guest'])
            ->get('oidc/link/choice', 'Blessing\OAuth\OIDC\OIDCLinkController@showChoice')
            ->name('oidc.link.choice');

        // 新用户创建（选择页 B 选项 1）
        $router->middleware(['web', 'guest'])
            ->post('oidc/link/create', 'Blessing\OAuth\OIDC\OIDCLinkController@createNewAccount')
            ->name('oidc.link.create');

        // 自动绑定已有账户（选择页 A 选项 1）
        $router->middleware(['web', 'guest'])
            ->post('oidc/link/auto-link', 'Blessing\OAuth\OIDC\OIDCLinkController@autoLink')
            ->name('oidc.link.auto-link');

        // 登录后完成关联（选择页 A/B 选项 2，先登录再跳转至此）
        $router->middleware(['web', 'auth'])
            ->get('oidc/link/complete', 'Blessing\OAuth\OIDC\OIDCLinkController@completeLink')
            ->name('oidc.link.complete');

        // 前端 JS 轮询接口，检查是否有待关联
        $router->middleware(['web', 'auth'])
            ->get('oidc/link/check-pending', 'Blessing\OAuth\OIDC\OIDCLinkController@checkPending');
    });
};
