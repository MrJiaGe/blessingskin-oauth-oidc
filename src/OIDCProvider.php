<?php

namespace Blessing\OAuth\OIDC;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class OIDCProvider extends AbstractProvider
{
    const IDENTIFIER = 'OIDC';
    protected $scopes = ['openid', 'profile', 'email'];

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(config('services.oidc.authorize_url'), $state);
    }

    protected function getTokenUrl()
    {
        return config('services.oidc.token_url');
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(config('services.oidc.userinfo_url'), [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

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
