<?php

use Blessing\Filter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Blessing\OAuth\OIDC\OIDCExtendSocialite;

return function (Dispatcher $events, Filter $filter) {
    // 注册 OIDC Provider
    $events->listen(SocialiteWasCalled::class, [OIDCExtendSocialite::class, 'handle']);

    // OIDC 服务配置，通过 .env 注入
    config(['services.oidc' => [
        'client_id'     => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect'      => env('OIDC_REDIRECT_URI'),
        
    // OIDC 标准端点（从 .well-known/openid-configuration 拿到）
        'authorize_url' => env('OIDC_AUTHORIZE_URL'),
        'token_url'     => env('OIDC_TOKEN_URL'),
        'userinfo_url'  => env('OIDC_USERINFO_URL'),
    ]]);

    // 增加登录按钮
    $filter->add('oauth_providers', function (Collection $providers) {
        $providers->put('oidc', [
            'icon' => 'fa-user-circlc',
            'displayName' => env('OIDC_DISPLAY_NAME','OIDC'),
        ]);
        return $providers;
    });
};