<?php

namespace Blessing\OAuth\OIDC;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

        $binding = $query->first();

        Log::debug('OIDC Binding: 查询绑定', [
            'sub' => $sub,
            'issuer' => $issuer,
            'found' => !is_null($binding),
            'binding_id' => $binding ? $binding->id : null,
        ]);

        return $binding;
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
        $issuer = $issuer ?: null;

        Log::info('OIDC Binding: 开始创建/更新绑定', [
            'uid' => $uid,
            'sub' => $sub,
            'issuer' => $issuer,
        ]);

        $query = self::where('oidc_sub', $sub);

        if ($issuer !== null) {
            $query->where('oidc_issuer', $issuer);
        } else {
            $query->where(function ($q) {
                $q->whereNull('oidc_issuer')
                    ->orWhere('oidc_issuer', '');
            });
        }

        $binding = $query->first();

        if (!$binding) {
            $binding = new self();
            $binding->uid = $uid;
            $binding->oidc_sub = $sub;
            $binding->oidc_issuer = $issuer;

            if (!$binding->save()) {
                Log::error('OIDC Binding: 创建绑定失败', [
                    'uid' => $uid,
                    'sub' => $sub,
                    'issuer' => $issuer,
                ]);
                throw new \RuntimeException('Failed to create OIDC binding');
            }

            Log::info('OIDC Binding: 绑定创建成功', [
                'id' => $binding->id,
                'uid' => $uid,
                'sub' => $sub,
            ]);
        } elseif ($binding->uid !== $uid) {
            $oldUid = $binding->uid;
            $binding->uid = $uid;

            if (!$binding->save()) {
                Log::error('OIDC Binding: 更新绑定失败', [
                    'id' => $binding->id,
                    'old_uid' => $oldUid,
                    'new_uid' => $uid,
                ]);
                throw new \RuntimeException('Failed to update OIDC binding');
            }

            Log::info('OIDC Binding: 绑定更新成功', [
                'id' => $binding->id,
                'old_uid' => $oldUid,
                'new_uid' => $uid,
            ]);
        } else {
            Log::debug('OIDC Binding: 绑定已存在且未变化', [
                'id' => $binding->id,
                'uid' => $uid,
            ]);
        }

        return $binding;
    }
}
