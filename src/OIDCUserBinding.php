<?php

namespace Blessing\OAuth\OIDC;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

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
        $query = self::where('oidc_sub', $sub);

        if ($issuer !== null) {
            $query->where('oidc_issuer', $issuer);
        } else {
            $query->whereNull('oidc_issuer');
        }

        return $query->first();
    }

    /**
     * Create or update binding for a user
     *
     * @param int $uid BlessingSkin user ID
     * @param string $sub OIDC subject claim
     * @param string|null $issuer OIDC issuer URL
     * @return static
     */
    public static function bindUser(int $uid, string $sub, ?string $issuer = null): self
    {
        $query = self::where('oidc_sub', $sub);

        if ($issuer !== null) {
            $query->where('oidc_issuer', $issuer);
        } else {
            $query->whereNull('oidc_issuer');
        }

        $binding = $query->first();

        if (!$binding) {
            $binding = new self();
            $binding->uid = $uid;
            $binding->oidc_sub = $sub;
            $binding->oidc_issuer = $issuer;
            $binding->save();
        } elseif ($binding->uid !== $uid) {
            $binding->uid = $uid;
            $binding->save();
        }

        return $binding;
    }
}
