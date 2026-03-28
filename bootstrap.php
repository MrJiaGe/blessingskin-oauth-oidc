<?php

use App\Services\Hook;
use Blessing\Filter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Manager\ServiceProvider as SocialiteServiceProvider;
use Blessing\OAuth\OIDC\OIDCExtendSocialite;
use Blessing\OAuth\OIDC\OIDCProvider;

return function (Dispatcher $events, Filter $filter) {
    // OIDC 服务配置，通过 .env 注入（必须在注册 driver 之前配置）
    config(['services.oidc' => [
        'client_id'     => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect'      => env('OIDC_REDIRECT_URI'),

    // OIDC 标准端点（从 .well-known/openid-configuration 拿到）
        'authorize_url' => env('OIDC_AUTHORIZE_URL'),
        'token_url'     => env('OIDC_TOKEN_URL'),
        'userinfo_url'  => env('OIDC_USERINFO_URL'),
    ]]);

    // 确保 SocialiteProviders 服务提供者已注册
    try {
        app()->register(SocialiteServiceProvider::class);
    } catch (\Exception $e) {
        // 已注册，忽略
    }

    // 注册 OIDC Provider - 方式1: 通过事件监听
    $events->listen(SocialiteWasCalled::class, [OIDCExtendSocialite::class, 'handle']);

    // 注册 OIDC Provider - 方式2: 直接扩展（确保 driver 可用，不依赖事件触发顺序）
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

    // 增加登录按钮
    $filter->add('oauth_providers', function (Collection $providers) {
        $providers->put('oidc', [
            'icon' => 'fa-user-circle',
            'displayName' => env('OIDC_DISPLAY_NAME', 'OIDC'),
        ]);
        return $providers;
    });

    // 注册独立回调路由，避免与 OAuth 核心插件路由冲突
    Hook::addRoute(function ($router) {
        $router->middleware(['web', 'guest'])
            ->get('oidc/callback', 'Blessing\OAuth\OIDC\OIDCAuthController@callback');
    });
};