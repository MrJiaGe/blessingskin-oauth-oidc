<?php

namespace Blessing\OAuth\OIDC;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OIDC 绑定管理控制器（管理面板）
 *
 * 提供绑定列表、字段编辑、整行删除功能。
 * 所有方法均要求管理员权限（路由层 role:admin + 控制器层 isAdmin 双重校验）。
 */
class OIDCAdminController extends Controller
{
    /**
     * OIDC sub 合法字符白名单：字母、数字、URI 安全字符
     * 允许：a-z A-Z 0-9 - _ . / : @ + = ~
     */
    const SUB_PATTERN = '/^[a-zA-Z0-9\-_.\/:@+=~]+$/';

    /**
     * Issuer 合法字符白名单：标准 URI 字符
     * 允许：a-z A-Z 0-9 - _ . / : @ + = ~ ? # % &
     */
    const ISSUER_PATTERN = '/^[a-zA-Z0-9\-_.\/:@+=~?#%&]+$/';

    /** 每页条数白名单 */
    const PAGE_SIZES = [20, 50, 100, 500];

    /** 字段最大长度 */
    const MAX_SUB_LENGTH = 255;
    const MAX_ISSUER_LENGTH = 512;

    /**
     * 绑定列表页
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            Log::warning('OIDC Admin: 非管理员尝试访问绑定管理', [
                'uid' => $user ? $user->uid : null,
                'ip' => $request->ip(),
            ]);
            abort(403, 'Forbidden');
        }

        $page = max(1, (int) $request->get('page', 1));
        $perPage = (int) $request->get('per_page', 20);
        $showAll = $request->get('per_page') === 'all';

        // 校验 per_page 合法性
        if (!$showAll && !in_array($perPage, self::PAGE_SIZES, true)) {
            $perPage = 20;
        }

        Log::debug('OIDC Admin: 查询绑定列表', [
            'admin_uid' => $user->uid,
            'page' => $page,
            'per_page' => $showAll ? 'all' : $perPage,
        ]);

        // 基础查询：绑定表 LEFT JOIN 用户表
        $baseQuery = DB::table('oidc_user_bindings as b')
            ->leftJoin('users as u', 'b.uid', '=', 'u.uid')
            ->orderBy('b.uid', 'asc')
            ->orderBy('b.id', 'asc')
            ->orderBy('b.updated_at', 'asc');

        $total = $baseQuery->count();

        if ($showAll) {
            $bindings = $baseQuery->select(
                'b.id',
                'b.uid',
                'b.oidc_sub',
                'b.oidc_issuer',
                'b.created_at',
                'b.updated_at',
                'u.email',
                'u.nickname'
            )->get();
            $totalPages = 1;
        } else {
            $offset = ($page - 1) * $perPage;
            $bindings = $baseQuery->select(
                'b.id',
                'b.uid',
                'b.oidc_sub',
                'b.oidc_issuer',
                'b.created_at',
                'b.updated_at',
                'u.email',
                'u.nickname'
            )->offset($offset)->limit($perPage)->get();
            $totalPages = max(1, (int) ceil($total / $perPage));
        }

        // 格式化时间
        foreach ($bindings as $binding) {
            $binding->updated_at_formatted = $binding->updated_at
                ? date('Y-m-d H:i:s', strtotime($binding->updated_at))
                : '-';
        }

        Log::debug('OIDC Admin: 查询完成', [
            'total' => $total,
            'showing' => count($bindings),
            'page' => $page,
            'total_pages' => $totalPages,
        ]);

        return view('Blessing\OAuth\OIDC::admin-bindings', [
            'bindings' => $bindings,
            'total' => $total,
            'page' => $page,
            'perPage' => $showAll ? 'all' : $perPage,
            'totalPages' => $totalPages,
            'pageSizes' => self::PAGE_SIZES,
            'pageConfigJson' => $this->buildPageConfigJson($showAll ? 'all' : $perPage),
        ]);
    }

    /**
     * 构建注入页面的配置 JSON
     *
     * 作为独立 JS 文件（admin-bindings.js）的唯一数据源，
     * 包含接口地址、当前分页大小与前端文案（i18n）。
     *
     * @param int|string $perPage 当前每页条数，'all' 表示全部
     * @return string JSON 字符串
     */
    private function buildPageConfigJson($perPage): string
    {
        $config = [
            'urls' => [
                'updateField' => url('admin/oidc/update-field'),
                'delete' => url('admin/oidc/delete'),
            ],
            'perPage' => $perPage,
            'i18n' => [
                'edit_sub' => trans('Blessing\OAuth\OIDC::general.admin_edit_sub'),
                'edit_issuer' => trans('Blessing\OAuth\OIDC::general.admin_edit_issuer'),
                'hint_sub' => trans('Blessing\OAuth\OIDC::general.admin_hint_sub'),
                'hint_issuer' => trans('Blessing\OAuth\OIDC::general.admin_hint_issuer'),
                'err_empty' => trans('Blessing\OAuth\OIDC::general.admin_err_empty'),
                'err_illegal' => trans('Blessing\OAuth\OIDC::general.admin_err_illegal'),
                'err_danger' => trans('Blessing\OAuth\OIDC::general.admin_err_danger'),
                'err_too_long' => trans('Blessing\OAuth\OIDC::general.admin_err_too_long'),
                'large_page_warn' => trans('Blessing\OAuth\OIDC::general.admin_large_page_warn'),
                'saving' => trans('Blessing\OAuth\OIDC::general.admin_saving'),
                'deleting' => trans('Blessing\OAuth\OIDC::general.admin_deleting'),
                'confirm' => trans('Blessing\OAuth\OIDC::general.admin_confirm'),
                'delete_btn' => trans('Blessing\OAuth\OIDC::general.admin_delete_confirm_btn'),
            ],
        ];

        // JSON_HEX_TAG：转义 < > 防止 </script> 闭合注入
        // JSON_UNESCAPED_UNICODE：中文直接输出，便于排查
        return json_encode(
            $config,
            JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * 编辑单个字段（oidc_sub 或 oidc_issuer）
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateField(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            Log::warning('OIDC Admin: 非管理员尝试编辑绑定', [
                'uid' => $user ? $user->uid : null,
                'ip' => $request->ip(),
            ]);
            return json('Forbidden', 1);
        }

        $bindingId = $request->input('id');
        $field = $request->input('field');
        $value = trim($request->input('value', ''));

        Log::info('OIDC Admin: 编辑请求', [
            'admin_uid' => $user->uid,
            'binding_id' => $bindingId,
            'field' => $field,
            'value_length' => strlen($value),
        ]);

        // ── 校验绑定 ID ──
        if (!is_numeric($bindingId) || (int) $bindingId < 1) {
            Log::warning('OIDC Admin: 无效的绑定 ID', ['id' => $bindingId]);
            return json(trans('Blessing\OAuth\OIDC::general.admin_invalid_id'), 1);
        }
        $bindingId = (int) $bindingId;

        // ── 校验字段名 ──
        if (!in_array($field, ['oidc_sub', 'oidc_issuer'], true)) {
            Log::warning('OIDC Admin: 无效的字段名', ['field' => $field]);
            return json(trans('Blessing\OAuth\OIDC::general.admin_invalid_field'), 1);
        }

        // ── 校验值 ──
        if ($value === '') {
            // issuer 允许清空（置为 null）
            if ($field === 'oidc_issuer') {
                $value = null;
            } else {
                Log::warning('OIDC Admin: sub 不允许为空');
                return json(trans('Blessing\OAuth\OIDC::general.admin_value_empty'), 1);
            }
        }

        if ($value !== null) {
            // 长度校验
            $maxLen = ($field === 'oidc_sub') ? self::MAX_SUB_LENGTH : self::MAX_ISSUER_LENGTH;
            if (strlen($value) > $maxLen) {
                Log::warning('OIDC Admin: 值超长', [
                    'field' => $field,
                    'length' => strlen($value),
                    'max' => $maxLen,
                ]);
                return json(trans('Blessing\OAuth\OIDC::general.admin_value_too_long'), 1);
            }

            // 白名单正则校验
            $pattern = ($field === 'oidc_sub') ? self::SUB_PATTERN : self::ISSUER_PATTERN;
            if (!preg_match($pattern, $value)) {
                Log::warning('OIDC Admin: 值包含非法字符', [
                    'field' => $field,
                    'value_preview' => substr($value, 0, 32) . '...',
                ]);
                return json(trans('Blessing\OAuth\OIDC::general.admin_illegal_chars'), 1);
            }

            // 注入特征检测（纵深防御，正则已覆盖大部分，这里做关键词兜底）
            $dangerPatterns = [
                '/\b(DROP|DELETE|INSERT|UPDATE|ALTER|CREATE|TRUNCATE|EXEC|EXECUTE)\b/i',
                '/\b(UNION\s+SELECT|OR\s+1\s*=\s*1|AND\s+1\s*=\s*1)\b/i',
                '/(--|\/\*|\*\/|;)/',
            ];
            foreach ($dangerPatterns as $dp) {
                if (preg_match($dp, $value)) {
                    Log::warning('OIDC Admin: 检测到注入特征', [
                        'field' => $field,
                        'pattern' => $dp,
                    ]);
                    return json(trans('Blessing\OAuth\OIDC::general.admin_illegal_chars'), 1);
                }
            }
        }

        // ── 查找绑定记录 ──
        $binding = OIDCUserBinding::find($bindingId);
        if (!$binding) {
            Log::warning('OIDC Admin: 绑定记录不存在', ['id' => $bindingId]);
            return json(trans('Blessing\OAuth\OIDC::general.admin_not_found'), 1);
        }

        // ── 检查唯一性冲突（修改 sub 时） ──
        if ($field === 'oidc_sub' && $value !== $binding->oidc_sub) {
            $existing = OIDCUserBinding::where('oidc_sub', $value)
                ->where(function ($q) use ($binding) {
                    $q->where('oidc_issuer', $binding->oidc_issuer)
                      ->orWhere(function ($q2) {
                          $q2->whereNull('oidc_issuer')->orWhere('oidc_issuer', '');
                      });
                })
                ->where('id', '!=', $bindingId)
                ->first();
            if ($existing) {
                Log::warning('OIDC Admin: sub 已被其他记录占用', [
                    'sub' => $value,
                    'conflict_id' => $existing->id,
                ]);
                return json(trans('Blessing\OAuth\OIDC::general.admin_sub_conflict'), 1);
            }
        }

        // ── 执行更新 ──
        $oldValue = $binding->{$field};
        $binding->{$field} = $value;
        $binding->updated_at = now();

        if (!$binding->save()) {
            Log::error('OIDC Admin: 保存失败', [
                'id' => $bindingId,
                'field' => $field,
            ]);
            return json(trans('Blessing\OAuth\OIDC::general.admin_save_failed'), 1);
        }

        Log::info('OIDC Admin: 字段更新成功', [
            'admin_uid' => $user->uid,
            'binding_id' => $bindingId,
            'field' => $field,
            'old_value' => substr((string) $oldValue, 0, 32),
            'new_value' => substr((string) $value, 0, 32),
        ]);

        return json(trans('Blessing\OAuth\OIDC::general.admin_update_success'), 0);
    }

    /**
     * 删除绑定记录（整行删除）
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            Log::warning('OIDC Admin: 非管理员尝试删除绑定', [
                'uid' => $user ? $user->uid : null,
                'ip' => $request->ip(),
            ]);
            return json('Forbidden', 1);
        }

        $bindingId = $request->input('id');

        Log::info('OIDC Admin: 删除请求', [
            'admin_uid' => $user->uid,
            'binding_id' => $bindingId,
        ]);

        if (!is_numeric($bindingId) || (int) $bindingId < 1) {
            Log::warning('OIDC Admin: 无效的绑定 ID', ['id' => $bindingId]);
            return json(trans('Blessing\OAuth\OIDC::general.admin_invalid_id'), 1);
        }
        $bindingId = (int) $bindingId;

        $binding = OIDCUserBinding::find($bindingId);
        if (!$binding) {
            Log::warning('OIDC Admin: 绑定记录不存在', ['id' => $bindingId]);
            return json(trans('Blessing\OAuth\OIDC::general.admin_not_found'), 1);
        }

        // 记录被删除的信息用于审计
        $deletedInfo = [
            'id' => $binding->id,
            'uid' => $binding->uid,
            'oidc_sub' => substr($binding->oidc_sub, 0, 32),
            'oidc_issuer' => $binding->oidc_issuer,
        ];

        if (!$binding->delete()) {
            Log::error('OIDC Admin: 删除失败', ['id' => $bindingId]);
            return json(trans('Blessing\OAuth\OIDC::general.admin_delete_failed'), 1);
        }

        Log::info('OIDC Admin: 绑定记录已删除', array_merge(
            ['admin_uid' => $user->uid],
            $deletedInfo
        ));

        return json(trans('Blessing\OAuth\OIDC::general.admin_delete_success'), 0);
    }
}
