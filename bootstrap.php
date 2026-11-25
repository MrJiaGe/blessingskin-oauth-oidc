<?php

// ── 插件入口：注册 Provider、监听事件、注册路由 ──

use App\Events\UserLoggedIn;
use App\Services\Hook;
use Blessing\Filter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Manager\ServiceProvider as SocialiteServiceProvider;
use Blessing\OAuth\OIDC\OIDCExtendSocialite;
use Blessing\OAuth\OIDC\OIDCProvider;
use Blessing\OAuth\OIDC\OIDCSession;

return function (Dispatcher $events, Filter $filter, $plugin) {
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

    $events->listen(UserLoggedIn::class, function () {
        if (OIDCSession::getPending()) {
            Session::put('last_requested_path', route('oidc.link.confirm'));
        }
    });

    $filter->add('oauth_providers', function (Collection $providers) {
        if (OIDCSession::getPending()) {
            return collect();
        }

        $providers->put('oidc', [
            'icon' => 'fa-user-circle',
            'displayName' => env('OIDC_DISPLAY_NAME', 'OIDC'),
        ]);
        return $providers;
    });

    // 管理面板：绑定管理页面脚本（仅在 admin/oidc 加载）
    Hook::addScriptFileToPage($plugin->assets('admin-bindings.js'), ['admin/oidc']);

    // ── 管理面板菜单：OIDC → 绑定管理 ──
    Hook::addMenuItem('admin', 10, [
        'title' => 'Blessing\OAuth\OIDC::general.admin_menu_title',
        'link'  => 'admin/oidc',
        'icon'  => 'fa-key',
    ]);

    // ── 路由注册 ──
    Hook::addRoute(function ($router) {
        // OIDC 回调（IDP 授权后跳转至此）
        $router->middleware(['web', 'guest'])
            ->get('oidc/callback', 'Blessing\OAuth\OIDC\OIDCAuthController@callback')
            ->name('oidc.callback');

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

        $router->middleware(['web', 'authorize'])
            ->get('oidc/link/confirm', 'Blessing\OAuth\OIDC\OIDCLinkController@showConfirmation')
            ->name('oidc.link.confirm');

        $router->middleware(['web', 'authorize'])
            ->post('oidc/link/complete', 'Blessing\OAuth\OIDC\OIDCLinkController@completeLink')
            ->name('oidc.link.complete');

        // ── 管理面板：OIDC 绑定管理 ──
        $router->middleware(['web', 'auth', 'role:admin'])
            ->get('admin/oidc', 'Blessing\OAuth\OIDC\OIDCAdminController@index')
            ->name('admin.oidc');

        $router->middleware(['web', 'auth', 'role:admin'])
            ->post('admin/oidc/update-field', 'Blessing\OAuth\OIDC\OIDCAdminController@updateField');

        $router->middleware(['web', 'auth', 'role:admin'])
            ->post('admin/oidc/delete', 'Blessing\OAuth\OIDC\OIDCAdminController@destroy');
    });
};
