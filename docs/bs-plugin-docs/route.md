# 路由

> 来源：https://bs-plugin.netlify.app/guide/route.html

- [最简单的路由](#最简单的路由)
- [从路由中获取参数](#从路由中获取参数)
- [路由组](#路由组)
- [中间件](#中间件)
    - [web](#web)
    - [auth](#auth)
    - [role](#role)
    - [verified](#verified)
    - [用法](#用法)

通过 URI 路径访问，无需像访问静态文件路径那样，可以美化链接。

所有路由都在 `bootstrap.php` 中的闭包中添加：

```php
use App\Services\Hook;

return function () {
    Hook::addRoute(function ($routes) {
        // 在这里注册路由
    });
};
```

## 最简单的路由

可对不同的 HTTP 动作注册路由。同 URI 不同 HTTP 动作表示不同路由：

```php
Hook::addRoute(function ($routes) {
    $routes->get('foo/bar', 'FoobarController@showPage');
    $routes->post('foo/bar', 'FoobarController@foobar');
});
```

使用 `match` 注册多个 HTTP 动作的路由：

```php
$routes->match(['get', 'post'], 'foo/bar', 'FoobarController@doSomething');
```

`any` 响应所有 HTTP 动作：

```php
$routes->any('foo/bar', 'FoobarController@doSomething');
```

从 v4 起支持 `view` 和 `redirect` 方法：

- `view`：第二个参数为视图（避免仅为显示视图而定义控制器）
- `redirect`：第二个参数为目标 URL

**为使 Laravel 能缓存路由，建议以字符串形式指定控制器，而非闭包**：

```php
// 反面教材
$routes->any('foo/bar', function () {
    // 不推荐
});
```

通常 `'FoobarController@doSomething'` 格式：`FoobarController` 是类名，`doSomething` 是公有方法名。

> 由于插件代码总有不同于皮肤站程序的命名空间，必须指定该类的命名空间，除非使用路由组。

## 从路由中获取参数

```php
$routes->any('player/{skin_id}/{cape_id?}', 'FoobarController@doSomething');
```

参数名后加 `?` 表示可选参数。控制器中：

```php
public function doSomething($skin_id, $cape_id = null)
{
    //
}
```

## 路由组

共享几个路由的属性时使用路由组：

```php
$routes->group([
    'middleware' => ['web'],
    'prefix'     => 'hello',
    'namespace'  => 'Example\Foo\Bar'
], function ($route) {
    $route->get('world', 'ExampleController@example');
});
```

常用属性：

- `middleware`：中间件，字符串或字符串数组
- `prefix`：路由组前缀，上例前缀为 `hello`
- `namespace`：命名空间

## 中间件

中间件可对请求进行过滤，如未登录用户访问用户中心时，`auth` 中间件将检查登录状态。

Blessing Skin 提供的中间件：

### `web`

基本检查如 Cookies 等。一般请求建议使用。

### `auth`

检查访客是否已登录。

### `role` （v5.0.0+）

检查当前用户权限。接收必需的权限级别参数：

```php
Route::middleware('role:admin')-> //...
Route::middleware('role:super-admin')-> //...
```

### `verified` （v3.5.0+）

检查当前用户邮箱是否已验证。

### 用法

在路由组中使用中间件；非路由组：

```php
$routes->get('foo/bar', 'FoobarController@showPage')->middleware(['web', 'auth']);
```