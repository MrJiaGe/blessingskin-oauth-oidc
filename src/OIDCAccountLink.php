<?php

namespace Blessing\OAuth\OIDC;

use App\Models\User;
use Blessing\Filter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Vectorface\Whip\Whip;

class OIDCAccountLink
{
    public static function emailIsVerified(array $identity): bool
    {
        return ($identity['email_verified'] ?? false) === true
            && is_string($identity['email'])
            && strlen($identity['email']) <= 100
            && filter_var($identity['email'], FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function usersWithEmail(?string $email)
    {
        return User::where('email', $email)->limit(2);
    }

    public static function canAutoLink(array $identity, $matches): bool
    {
        if (!self::emailIsVerified($identity) || $matches->count() !== 1 || !$matches->first()->verified) {
            return false;
        }

        return !OIDCUserBinding::where('uid', $matches->first()->uid)->exists();
    }

    /** Create a local account and its OIDC binding as one operation. */
    public function register(array $identity, Filter $filter): User
    {
        if (!self::emailIsVerified($identity)) {
            $this->fail('verified_provider_email_required');
        }

        $ip = $filter->apply('client_ip', (new Whip())->getValidIpAddress());

        return DB::transaction(function () use ($identity, $ip) {
            $email = $identity['email'];
            if (self::usersWithEmail($email)->exists()) {
                $this->fail('email_in_use');
            }

            $user = new User();
            $user->email = $email;
            $user->nickname = mb_substr($identity['nickname'] ?: explode('@', $email)[0], 0, 50);
            $user->score = option('user_initial_score');
            $user->avatar = 0;
            $user->password = '';
            $user->ip = $ip;
            $user->permission = User::NORMAL;
            $user->register_at = Carbon::now();
            $user->last_sign_at = Carbon::now()->subDay();
            $user->verified = true;
            $user->save();

            OIDCUserBinding::bindUser($user->uid, $identity['sub'], $identity['issuer']);

            return $user;
        });
    }

    /** Match an existing account only when both sides have verified the email. */
    public function autoLink(array $identity): User
    {
        return DB::transaction(function () use ($identity) {
            $matches = self::usersWithEmail($identity['email'])->lockForUpdate()->get();
            if ($matches->count() === 1 && !$matches->first()->verified) {
                $this->fail('local_email_unverified');
            }
            if (!self::canAutoLink($identity, $matches)) {
                $this->fail('auto_link_unavailable');
            }

            $user = $matches->first();
            OIDCUserBinding::bindUser($user->uid, $identity['sub'], $identity['issuer']);

            return $user;
        });
    }

    /** Check whether a verified IdP email can replace the local email. */
    public function canChangeEmail(User $user, array $identity): bool
    {
        return self::emailIsVerified($identity)
            && $user->email !== $identity['email']
            && !self::usersWithEmail($identity['email'])->where('uid', '!=', $user->uid)->exists();
    }

    /** Allow replacement only when the existing identity is from this same issuer. */
    public function canReplaceBinding($bindings, array $identity): bool
    {
        return $bindings->count() === 1
            && ($bindings->first()->oidc_issuer ?: null) === $identity['issuer'];
    }

    /** Bind an authenticated user and apply explicitly confirmed changes together. */
    public function linkExisting(int $uid, array $identity, array $confirmation): void
    {
        DB::transaction(function () use ($uid, $identity, $confirmation) {
            $user = User::where('uid', $uid)->lockForUpdate()->firstOrFail();
            if (!$user->verified) {
                $this->fail('local_email_unverified');
            }
            if ($confirmation['local_email'] !== $user->email) {
                $this->fail('confirmation_changed');
            }

            $bindings = OIDCUserBinding::where('uid', $uid)->lockForUpdate()->limit(2)->get();
            $existing = $bindings->first();
            $bindingId = $existing ? (string) $existing->id : '';
            if ($confirmation['binding_id'] !== $bindingId) {
                $this->fail('confirmation_changed');
            }

            if ($existing && (!$confirmation['replace_binding']
                || !$this->canReplaceBinding($bindings, $identity)
                || $confirmation['old_sub'] !== $existing->oidc_sub)) {
                $this->fail('already_linked');
            }

            if ($confirmation['change_email']) {
                if (!$this->canChangeEmail($user, $identity)) {
                    $this->fail('email_change_unavailable');
                }

                $user->email = $identity['email'];
                $user->verified = true;
                $user->save();
            }

            $binding = OIDCUserBinding::bindUser($uid, $identity['sub'], $identity['issuer']);
            if ($existing && $existing->id !== $binding->id) {
                $existing->delete();
            }
        });
    }

    private function fail(string $key): void
    {
        throw ValidationException::withMessages(['oidc' => trans('Blessing\OAuth\OIDC::general.' . $key)]);
    }
}
