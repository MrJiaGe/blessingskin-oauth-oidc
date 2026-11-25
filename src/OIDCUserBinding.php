<?php

namespace Blessing\OAuth\OIDC;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * OIDC Subject to User Binding Model
 * 
 * @property int $id
 * @property int $uid
 * @property string $oidc_sub
 * @property string|null $oidc_issuer
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class OIDCUserBinding extends Model
{
    protected $table = 'oidc_user_bindings';

    protected $fillable = [
        'uid',
        'oidc_sub',
        'oidc_issuer',
    ];

    protected $casts = [
        'uid' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the BlessingSkin user associated with this binding
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }

    /**
     * Find binding by OIDC subject and issuer
     *
     * @param string $sub OIDC subject claim
     * @param string|null $issuer OIDC issuer URL (optional for single provider)
     * @return static|null
     */
    public static function findBySub(string $sub, ?string $issuer = null): ?self
    {
        $issuer = $issuer ?: null;

        $query = self::where('oidc_sub', $sub);

        if ($issuer !== null) {
            $query->where('oidc_issuer', $issuer);
        } else {
            $query->where(function ($q) {
                $q->whereNull('oidc_issuer')
                    ->orWhere('oidc_issuer', '');
            });
        }

        $matches = $query->limit(2)->get();
        $binding = $matches->first();
        if ($binding && ($matches->count() > 1 || $binding->oidc_sub !== $sub
            || ($binding->oidc_issuer ?: null) !== $issuer)) {
            throw ValidationException::withMessages([
                'oidc' => trans('Blessing\OAuth\OIDC::general.ambiguous_binding'),
            ]);
        }

        return $binding;
    }

    /**
     * Bind an identity to a user, or return the same user's existing binding.
     * The caller is responsible for explicitly removing a replaced identity.
     */
    public static function bindUser(int $uid, string $sub, ?string $issuer = null): self
    {
        $binding = self::findBySub($sub, $issuer);
        if ($binding) {
            if ($binding->uid !== $uid) {
                throw ValidationException::withMessages([
                    'oidc' => trans('Blessing\OAuth\OIDC::general.oidc_already_bound'),
                ]);
            }

            return $binding;
        }

        return self::create([
            'uid' => $uid,
            'oidc_sub' => $sub,
            'oidc_issuer' => $issuer ?: null,
        ]);
    }
}
