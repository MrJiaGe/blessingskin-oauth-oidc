# 迁移指南

> 来源：https://bs-plugin.netlify.app/guide/migration.html

## 从 4.3.x 或 4.4.0 迁移到 5.0.0

### 版本更新

- PHP 提升到 7.2.5
- Laravel 升级到 7.x
- AdminLTE v3（Bootstrap v4）

### `User` 模型

以下方法已移除：

- `setScore`（手动更新并 `save`）
- `getStorageUsed`
- `sign`
- `getSignRemainingTime`
- `canSign`

原静态方法 `getEncryptedPwdFromEvent` 现改为实例方法，方法签名改为只有一个参数 `$raw`。

### `Player` 模型

- v4 中废弃的 `player_name` 已移除
- `getJsonProfile` / `generateJsonProfile` / `getTexture` / `isBanned` 已移除

### 类导入

```php
// 移除以下用法
use Arr;
use Str;

// 改为
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
```

### `bootstrap.php`

可在闭包中通过 `$plugin` 参数获取当前插件实例：

```php
return function ($plugin) {
    // $plugin 就是这个插件的实例
    $plugin->assets('something.css');
};
```

Blessing Skin 会捕捉 `bootstrap.php` 中的异常（不包括路由或监听的事件内）。不希望被捕捉可在服务提供者中执行。

### 钩子

- `Hook::sendNotification` 第一个参数可接受单个用户
- `addScriptFileToPage` / `addStyleFileToPage` 不再支持第三个优先级参数
- `registerPluginTransScripts` 移除（v5 有新的加载方式）

### 中间件

- `player` 中间件已移除
- `admin` 和 `super-admin` 中间件已移除，取而代之 `role` 中间件（接受参数）

### 声明冲突

新增特性，详见 [声明插件冲突](conflicts.md)。

### 自动加载服务提供者

新增特性，详见 [服务提供者](providers.md)。

### `Response` 宏

`png` 宏和 `jsonProfile` 宏均已移除。

### 辅助函数

以下已移除：

- `format_http_date` → 用 `Carbon::toRfc7231String()`
- `nl2p` → 无替代
- `get_client_ip` → 推荐 `Vectorface/whip`
- `get_string_replaced` → 无替代

### `App\Services\Minecraft` 类

已移除。v5 使用新的预览库 [`blessing/texture-renderer`](https://github.com/bs-community/texture-renderer)。

### 配置页面

可通过 `enchants.config` 字段指定，优先级高于根 `config`。

### `blessing` 全局变量

- `notify.showModal` 用法完全改变
- `fallback_locale` 属性已移除

### 事件

以下事件已移除：

- `GetPlayerJson`
- `GetAvatarPreview`
- `GetSkinPreview`
- `CheckPlayerExists`
- `EncryptUserPassword`（请使用 filter `user_password`）
- `HashingFile`（请使用 filter `file_hash`）
- 前端的 `skinViewerMounted`
- 前端的 `mounted` 不再传递任何参数

### 表单

`OptionForm` 上新增 `addAlert` 方法，用法与 `addMessage` 类似。

### 其它

Parsedown 已从服务容器中移除，`app('parsedown')` 不再可用。

---

## 从 4.2.0 迁移到 4.3.0

### 钩子

- `addMenuItem` 支持新类型 `explore`，且支持 `new-tab: true`
- 新增 [`addUserBadge`](bootstrap.md#显示用户-badge-badge-text-4-3-0)
- 新增 [`sendNotification`](bootstrap.md#发送通知-badge-text-4-3-0)
- 新增 [`pushMiddleware`](bootstrap.md#注册中间件-badge-text-4-3-0)

---

## 从 4.1.0 迁移到 4.2.0

### 事件（后端）

- 新增事件 `TextureDeleting`

---

## 从 4.0.0 迁移到 4.1.0

### `json` 辅助函数（影响度：高）

行为有变更。两参数版本响应字段从 `errno/msg` 改为 `code/message`：

```json
// 之前
{ "errno": 0, "msg": "" }

// 现在
{ "code": 0, "message": "" }
```

三参数版本会把第三个参数包入 `data` 字段：

```json
// 之前
json('text', 0, ['abc' => 123]);
// { "errno": 0, "msg": "text", "abc": 123 }

// 现在
{ "code": 0, "message": "text", "data": { "abc": 123 } }
```

### 事件

- 后端新增 `PlayerRetrieved`
- 前端新增 `i18nLoaded`

---

## 从 3.5.0 迁移到 4.0.0

### 底层升级（影响度：高）

Laravel 升级到 5.8，PHP 需 7.1.8+。

### 用户认证（影响度：高）

重构为 Laravel 自带的认证系统：

```php
auth()->id();      // 当前用户的 UID
auth()->user();    // 当前用户的模型实例
auth()->check();   // 检查是否登录
```

### 前端浏览器兼容性（影响度：低）

不再支持 IE。可放心使用 ES 2015，更新版本建议 Babel 转码。

### Font Awesome（影响度：中）

升级到 v5。

### `Utils` 类（影响度：高）

`App\Services\Utils` 类已被彻底移除，取而代之是 [辅助函数](helpers.md)。

### 视图文件（影响度：中）

不再支持 `.tpl` 后缀，请使用 `.blade.php`。

### 前端函数（影响度：高）

由于使用 webpack 进行前端构建，v3 中大部分全局 JS 函数不再可用。

- `trans`：仍可用
- `jQuery`：全局可用
- `showModal`：移至 `blessing.notify`，功能保持
- `showMsg`：已移除
- `swal`、`toastr`：已移除
- `fetch`：v4 起使用浏览器原生 Fetch API
- `showAjaxError`：不再对外暴露

### DOM 修改（影响度：高）

使用 Vue.js 渲染的页面应通过 `blessing.event.on('mounted')` 在事件触发后修改 DOM。**不要修改 Vue 管理的 DOM 元素**（可以读取）。

### 前端多语言（影响度：中）

所有多语言存储在 `blessing.i18n` 下：

```js
blessing.i18n.configGenerator = { key1: 'text1' }
```

使用全局 `trans` 函数：`trans('configGenerator.key1.text1')`。

### `User` 模型（影响度：中）

- `changePasswd` → `changePassword`
- 移除 `register`、`getToken`、`setEmail`、`getNickName`、`setNickName`、`getScore`、`getAvatarId`、`setAvatar`、`getLastSignTime`、`getPermission`、`setPermission`

### `Player` 模型（影响度：高）

- 角色不再拥有「模型」概念
- `preference`、`tid_steve`、`tid_alex` 字段已移除，改用 `tid_skin`
- `player_name` 更名为 `name`
- 移除 `rename`、`setOwner`、`clearTexture`、`setTexture`、`checkForInvalidTextures`

### `Texture` 模型（影响度：中）

- 移除 `setPrivacy`，直接 `$texture->public = ...; $texture->save();`
- `likes` 字段只读

### `Closet` 模型（影响度：高）

`Closet` 模型已移除，请使用 `$user->closet()->get()`。

### 中间件（影响度：低）

`admin` 中间件不再先运行 `CheckAuthenticated` 中间件（仍安全，前提是同时使用 `auth` 中间件）。

### 辅助函数（影响度：中）

- `get_db_type` → `humanize_db_type`
- 移除：`runtime_check`、`die_with_utf8_encoding`、`bs_announcement`、`can_moderate_texture`、`menv`、`assets`、`get_current_url`

### Composer 依赖（影响度：低）

移除 `Datatables` 扩展包（`printempw/laravel-datatables-lite`）。如有依赖需自行安装。

### 事件（影响度：低）

- `EncryptUserPassword` 中 `rawPasswd` 更名为 `raw`

### 表单验证规则（影响度：低）

以下规则已移除：`playername`、`nickname`、`pname_chinese`。

---

## 从 3.4.0 迁移到 3.5.0

### Composer

Blessing Skin 支持插件使用 Composer 安装依赖，发布时把 `vendor` 一起打包。

### 明确 Blessing Skin 的版本

`package.json` 添加 `require` 字段指定依赖。建议明确 Blessing Skin 版本。

### `Utils` 类废弃

已被废弃，部分方法在 3.5.0 中已移除。