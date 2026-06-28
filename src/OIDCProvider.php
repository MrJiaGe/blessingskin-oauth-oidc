<?php

namespace Blessing\OAuth\OIDC;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/** OIDC Socialite Provider：实现 OAuth2 + OIDC 协议的标准端点 */
class OIDCProvider extends AbstractProvider
{
    const IDENTIFIER = 'OIDC';

    /** 请求 openid、profile、email 三个标准 scope */
    protected $scopes = ['openid', 'profile', 'email'];

    protected $scopeSeparator = ' ';

    /** 构造授权 URL */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(config('services.oidc.authorize_url'), $state);
    }

    /** 获取 Token 的端点 */
    protected function getTokenUrl()
    {
        return config('services.oidc.token_url');
    }

    /** 用 access_token 换取用户信息 (UserInfo) */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(config('services.oidc.userinfo_url'), [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /** 将 OIDC Claims 映射为 Socialite User 对象 */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['sub'] ?? null,
            'nickname' => $user['preferred_username'] ?? null,
            'name'     => $user['name'] ?? null,
            'email'    => $user['email'] ?? null,
            'avatar'   => $user['picture'] ?? null,
        ]);
    }
}
