<?php

namespace Blessing\OAuth\OIDC;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * OIDC 会话状态管理
 * 
 * 用于在账号关联流程中临时存储 OIDC 用户信息
 * 
 * 使用 Cache 作为持久化存储，避免 Session 在登录过程中丢失
 */
class OIDCSession
{
    const SESSION_KEY = 'oidc_pending_link';
    const CACHE_PREFIX = 'oidc_pending_';
    const CACHE_TTL = 600;

    /**
     * 生成缓存 key
     *
     * @return string
     */
    protected static function getCacheKey(): string
    {
        return self::CACHE_PREFIX . self::getSessionToken();
    }

    /**
     * 获取或生成 Session Token
     * 用于在 Session regenerate 时保持数据关联
     *
     * @return string
     */
    protected static function getSessionToken(): string
    {
        $token = Session::get('oidc_token');
        if (!$token) {
            $token = bin2hex(random_bytes(16));
            Session::put('oidc_token', $token);
            Session::save();
        }
        return $token;
    }

    /**
     * 存储待关联的 OIDC 用户信息
     *
     * @param array $data OIDC 用户数据
     * @return void
     */
    public static function storePending(array $data): void
    {
        $token = self::getSessionToken();
        
        $sessionData = [
            'sub' => $data['sub'],
            'email' => $data['email'],
            'nickname' => $data['nickname'] ?? '',
            'issuer' => $data['issuer'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'expires_at' => time() + self::CACHE_TTL,
        ];

        Session::put(self::SESSION_KEY, $sessionData);
        Session::save();

        Cache::put(self::CACHE_PREFIX . $token, $sessionData, self::CACHE_TTL);
    }

    /**
     * 获取待关联的 OIDC 用户信息
     *
     * @return array|null 已过期或不存在则返回 null
     */
    public static function getPending(): ?array
    {
        $data = Session::get(self::SESSION_KEY);

        if (!$data || !is_array($data)) {
            $token = Session::get('oidc_token');
            if ($token) {
                $data = Cache::get(self::CACHE_PREFIX . $token);
            }
        }

        if (!$data || !is_array($data)) {
            return null;
        }

        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            self::clear();
            return null;
        }

        return $data;
    }

    /**
     * 清除待关联的 OIDC 用户信息
     *
     * @return void
     */
    public static function clear(): void
    {
        $token = Session::get('oidc_token');
        if ($token) {
            Cache::forget(self::CACHE_PREFIX . $token);
        }

        Session::forget(self::SESSION_KEY);
        Session::forget('oidc_token');
        Session::forget('oidc_pending_token');
    }

    /**
     * 检查是否存在待关联的会话
     *
     * @return bool
     */
    public static function hasPending(): bool
    {
        return self::getPending() !== null;
    }

    /**
     * 登录后恢复 OIDC Session 数据
     * 在用户登录后调用，确保数据不会因 Session regenerate 而丢失
     *
     * @return void
     */
    public static function restoreAfterLogin(): void
    {
        $token = Session::get('oidc_token');
        if (!$token) {
            return;
        }

        $cachedData = Cache::get(self::CACHE_PREFIX . $token);
        if ($cachedData && is_array($cachedData)) {
            if (!isset($cachedData['expires_at']) || $cachedData['expires_at'] >= time()) {
                Session::put(self::SESSION_KEY, $cachedData);
            } else {
                Cache::forget(self::CACHE_PREFIX . $token);
            }
        }
    }

    /**
     * 获取当前 Session Token
     * 用于在 URL 中传递，解决 Session regenerate 导致的数据丢失问题
     *
     * @return string
     */
    public static function getToken(): string
    {
        return self::getSessionToken();
    }

    /**
     * 通过 Token 获取待关联的 OIDC 用户信息
     * 用于从 URL 参数恢复数据，不依赖 Session
     *
     * @param string $token
     * @return array|null 已过期或不存在则返回 null
     */
    public static function getPendingByToken(string $token): ?array
    {
        if (empty($token)) {
            return null;
        }

        $data = Cache::get(self::CACHE_PREFIX . $token);

        if (!$data || !is_array($data)) {
            return null;
        }

        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            Cache::forget(self::CACHE_PREFIX . $token);
            return null;
        }

        return $data;
    }

    /**
     * 通过 Token 清除待关联的 OIDC 用户信息
     *
     * @param string $token
     * @return void
     */
    public static function clearByToken(string $token): void
    {
        if (!empty($token)) {
            Cache::forget(self::CACHE_PREFIX . $token);
        }
    }
}
