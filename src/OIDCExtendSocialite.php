<?php

namespace Blessing\OAuth\OIDC;

use SocialiteProviders\Manager\SocialiteWasCalled;

/** 将 OIDCProvider 注册到 Socialite 驱动管理器 */
class OIDCExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('oidc', OIDCProvider::class);
    }
}
