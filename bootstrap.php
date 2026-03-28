<?php

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
    View::addNamespace('Blessing\OAuth\OIDC', __DIR__.'/resources/views');

    config(['services.oidc' => [
        'client_id'     => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect'      => env('OIDC_REDIRECT_URI'),

        'authorize_url' => env('OIDC_AUTHORIZE_URL'),
        'token_url'     => env('OIDC_TOKEN_URL'),
        'userinfo_url'  => env('OIDC_USERINFO_URL'),
    ]]);

    try {
        app()->register(SocialiteServiceProvider::class);
    } catch (\Exception $e) {
    }

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

    // 在登录页面加载时捕获并保存 token
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

    // 用户登录成功事件处理
    $events->listen(UserLoggedIn::class, function ($event) {
        $user = $event->user;

        Log::debug('OIDC Session: UserLoggedIn 事件触发', [
            'user_id' => $user->uid ?? null,
        ]);

        $token = Session::get('oidc_pending_token');

        if ($token) {
            $pending = OIDCSession::getPendingByToken($token);
            if ($pending) {
                Session::put('last_requested_path', url('/oidc/link/complete?token=' . $token));

                Log::info('OIDC: 用户登录成功，设置重定向到关联页面', [
                    'user_id' => $user->uid,
                    'token' => substr($token, 0, 8) . '...',
                ]);
            }
        }

        OIDCSession::restoreAfterLogin();
    });

    $filter->add('oauth_providers', function (Collection $providers) {
        // 如果存在 token 参数，说明是 OIDC 关联流程，隐藏所有 OAuth 选项
        if (request()->has('token')) {
            return collect();
        }

        $providers->put('oidc', [
            'icon' => 'fa-user-circle',
            'displayName' => env('OIDC_DISPLAY_NAME', 'OIDC'),
        ]);
        return $providers;
    });

    Hook::addScriptFileToPage($plugin->assets('link-redirect.js'), ['user']);

    Hook::addRoute(function ($router) {
        $router->middleware(['web', 'guest'])
            ->get('oidc/callback', 'Blessing\OAuth\OIDC\OIDCAuthController@callback');

        $router->middleware(['web', 'guest'])
            ->get('oidc/link/choice', 'Blessing\OAuth\OIDC\OIDCLinkController@showChoice')
            ->name('oidc.link.choice');

        $router->middleware(['web', 'guest'])
            ->post('oidc/link/create', 'Blessing\OAuth\OIDC\OIDCLinkController@createNewAccount')
            ->name('oidc.link.create');

        $router->middleware(['web', 'auth'])
            ->get('oidc/link/complete', 'Blessing\OAuth\OIDC\OIDCLinkController@completeLink')
            ->name('oidc.link.complete');

        $router->middleware(['web', 'auth'])
            ->get('oidc/link/check-pending', 'Blessing\OAuth\OIDC\OIDCLinkController@checkPending');
    });
};