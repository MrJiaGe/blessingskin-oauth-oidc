<?php

namespace Blessing\OAuth\OIDC;

use Illuminate\Support\Facades\Session;

class OIDCSession
{
    const SESSION_KEY = 'oidc_pending_link';

    /** Store the verified callback data for this browser's linking flow. */
    public static function storePending(array $identity): void
    {
        $identity['flow_id'] = bin2hex(random_bytes(16));
        $identity['expires_at'] = time() + 600;

        Session::put(self::SESSION_KEY, $identity);
    }

    public static function getPending(): ?array
    {
        $identity = Session::get(self::SESSION_KEY);
        if (!is_array($identity) || empty($identity['flow_id']) || ($identity['expires_at'] ?? 0) <= time()) {
            self::clear();
            return null;
        }

        return $identity;
    }

    /** Reject a form rendered before a new OIDC login replaced the pending identity. */
    public static function matchesForm(array $identity, $flowId): bool
    {
        return is_string($flowId) && hash_equals($identity['flow_id'], $flowId);
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
