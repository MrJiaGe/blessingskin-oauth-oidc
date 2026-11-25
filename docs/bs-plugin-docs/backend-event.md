# 事件监听（后端）

> 来源：https://bs-plugin.netlify.app/guide/backend-event.html

- [事件系统](#事件系统)
- [监听事件](#监听事件)
- [编写事件订阅者](#编写事件订阅者)
- [所有可用的事件](#所有可用的事件)

## 事件系统

Laravel 自带事件系统。在 `bootstrap.php` 返回的函数中监听事件：

```php
use Illuminate\Contracts\Events\Dispatcher;

return function (Dispatcher $events) {
    // ...
};
```

## 监听事件

`$events->listen(事件类, 回调)`。例如监听 `RenderingFooter`：

```php
$events->listen(App\Events\RenderingFooter::class, function ($event) {
    // $event-> ...
});
```

## 编写事件订阅者

订阅者是一个可在自身内部订阅多个事件的类，必须有 `subscribe` 方法：

```php
class RenderSubscriber
{
    public function onRenderingHeader($event)
    {
        //
    }

    public function onRenderingFooter($event)
    {
        //
    }

    public function subscribe($events)
    {
        // 假设插件命名空间是 `Example`
        $events->listen('App\Events\RenderingHeader', 'Example\RenderSubscriber@onRenderingHeader');
        $events->listen('App\Events\RenderingFooter', 'Example\RenderSubscriber@onRenderingFooter');
    }
}
```

在 `bootstrap.php` 中注册：

```php
$events->subscribe(Example\RenderSubscriber::class);
```

## 所有可用的事件

Blessing Skin 所有事件都在 `App\Events` 下。下面列出事件实例上的属性。

### `ConfigureAdminMenu`

**触发时机**：生成管理面板侧栏菜单之前。

- `menu`：数组，整个菜单列表

### `ConfigureRoutes`

**触发时机**：生成 Laravel 路由之前。

- `router`：`Illuminate\Routing\Router`，Laravel 路由对象

### `ConfigureUserMenu`

**触发时机**：生成普通用户界面侧栏菜单之前。

- `menu`：数组，整个菜单列表

### `PlayerProfileUpdated`

**触发时机**：角色信息更新之后。

- `player`：`App\Models\Player`，角色实例

### `PlayerRetrieved`

**触发时机**：从数据库中读取角色信息。

- `player`：`App\Models\Player`

### `PlayerWasAdded`

**触发时机**：角色被添加之后。

- `player`：`App\Models\Player`，新创建的实例

### `PlayerWasDeleted`

**触发时机**：角色被删除之后。

- `playerName`：字符串，被删除的角色名

### `PlayerWillBeAdded`

**触发时机**：角色即将被添加之前。

- `playerName`：字符串，即将添加的角色名

### `PlayerWillBeDeleted`

**触发时机**：角色被删除之前。

- `player`：`App\Models\Player`

### `PluginWasDeleted`

**触发时机**：插件被删除之前（命名有误导）。

- `plugin`：`App\Services\Plugin`，即将被删除的插件实例

### `PluginWasDisabled`

**触发时机**：插件被禁用之后。

- `plugin`：`App\Services\Plugin`

### `PluginWasEnabled`

**触发时机**：插件被启用之后。

- `plugin`：`App\Services\Plugin`

### `RenderingFooter`

**触发时机**：渲染页面底部 HTML 之后（Blessing Skin 自己的 JS 已加载完毕）。

- `contents`：字符串数组，逐个添加到页面底部
- `addContent(string)`：方法，向页面添加内容

```php
$event->addContent('<script></script>');
```

### `RenderingHeader`

**触发时机**：渲染页面顶部 HTML 之后（页面主体未渲染）。

- `contents`：字符串数组
- `addContent(string)`：方法

```php
$event->addContent('<style></style>');
```

### `TextureDeleting`

**触发时机**：材质将被删除。

- `contents`：`App\Models\Texture`

### `UserAuthenticated`

**触发时机**：用户认证之后。**不等同于登录**——只要用户已登录（含「记住我」）即触发。

- `user`：`App\Models\User`

### `UserLoggedIn`

**触发时机**：用户登录之后。

- `user`：`App\Models\User`

### `UserProfileUpdated`

**触发时机**：用户个人资料更新之后。

- `type`：字符串（`nickname` / `password` / `email`）
- `user`：`App\Models\User`

### `UserRegistered`

**触发时机**：用户注册之后。

- `user`：`App\Models\User`，新创建的实例

### `UserTryToLogin`

**触发时机**：用户尝试登录时（密码验证前）。

- `identification`：字符串，邮箱或角色名
- `authType`：字符串（`email` 或 `username`）