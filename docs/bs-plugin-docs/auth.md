# 用户认证

> 来源：https://bs-plugin.netlify.app/guide/auth.html
>
> ⚠️ 本节内容仅适用于 Blessing Skin v4 或更高版本

## 概览

Blessing Skin v4 使用了 Laravel 自带的认证系统。Laravel 的认证系统提供了一系列方法，您既可以通过 `Auth` Facade 来调用，也可以使用 `auth` 辅助函数。下面的演示都使用 `auth` 辅助函数。

## 检查用户是否已登录

通常应该使用 `auth` 中间件让请求只有在用户登录才能访问。手动检查可用 `check` 方法：

```php
if (auth()->check()) {
    // 用户已登录
}
```

## 获取当前用户的模型实例

使用 `user` 方法。未登录时返回 `null`，使用前无需调用 `check`：

```php
$user = auth()->user();
// $user 是 App\Models\User 实例
```

## 获取当前用户的 uid

```php
$uid = auth()->user()->uid;
// 或
$uid = auth()->id();
```

## Blade 与视图

Laravel 为 Blade 提供两个指令，可对当前认证情况进行判断：

### `@auth` 指令

```blade
@auth
    这里的内容只有在用户登录才能看到。
@endauth
```

### `@guest` 指令

```blade
@guest
    这里的内容只有未登录的用户才能看到。
@endguest
```