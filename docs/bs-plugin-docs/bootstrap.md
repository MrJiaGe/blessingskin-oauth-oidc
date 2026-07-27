# 插件的开始（`bootstrap.php`）

> 来源：https://bs-plugin.netlify.app/guide/bootstrap.html

- [返回闭包](#返回闭包)
- [钩子](#钩子)
    - [添加菜单项](#添加菜单项)
    - [添加路由](#添加路由)
    - [向页面添加样式文件](#向页面添加样式文件)
    - [向页面添加脚本文件](#向页面添加脚本文件)
    - [注册 JavaScript 的语言文件](#注册-javascript-的语言文件)
    - [显示用户 badge 4.3.0+](#显示用户-badge-badge-text-4-3-0)
    - [发送通知 4.3.0+](#发送通知-badge-text-4-3-0)
    - [注册中间件 4.3.0+](#注册中间件-badge-text-4-3-0)

`bootstrap.php` 是整个插件的入口文件，插件运行时，`bootstrap.php` 中的代码会首先被执行。

为了代码的模块化，业务逻辑不应放在此处，`bootstrap.php` 应只包含真正的逻辑代码前的准备代码，如对请求的判断和处理、菜单项的添加、路由、事件监听等。

## 返回闭包

`bootstrap.php` 是一个返回闭包的 PHP 文件。所有的代码都要写在这个匿名函数中。您可以在闭包参数列表中使用类型提示，Laravel 会自动从容器中解析对应的依赖并自动注入。

常用依赖：

- `Illuminate\Contracts\Events\Dispatcher` 事件监听依赖，详见 [事件](backend-event.md)。
- `Illuminate\Http\Request` 处理用户请求：HTTP 动词判断、获取路径、获取请求体数据、获取 Cookies 等。

从 5.0.0 起，可通过 `$plugin` 参数获取当前插件的实例：

```php
return function ($plugin) {
    // $plugin 就是这个插件的实例。

    // 可以像下面这样获取前端资源 URL：
    $plugin->assets('something.css');
};
```

## 钩子

Blessing Skin Server 提供了一些钩子供插件使用。调用前应 `use App\Services\Hook;`。所有钩子都是 `Hook` 类中的静态方法。

### 添加菜单项

向用户中心和管理面板的左侧菜单栏添加菜单项：

```php
Hook::addMenuItem('user', 0, [
    'title' => 'Blessing\ExamplePlugin::general.menu',
    'link'  => '/my-page',
    'icon'  => 'fa-cog',
    'new-tab' => true, // 是否在浏览器新标签页中打开链接，默认 false
]);
```

参数说明：

- 第一个参数：`user`、`explore`（用户中心下的「浏览」菜单）或 `admin`，分别表示用户中心或管理面板的左侧菜单栏。
- 第二个参数：菜单项位置，从 0 开始。过大则无法显示。
- 第三个参数：关联数组。
    - `title`：显示名，会经过翻译器翻译。需指明语言文件所在插件的命名空间。
    - `link`：相对或绝对路径。
    - `icon`：Font Awesome 图标名。

### 添加路由

详见 [路由](route.md)。

### 向页面添加样式文件

```php
Hook::addStyleFileToPage(plugin_assets('example-plugin', 'assets/css/example.css'));
// 或
Hook::addStyleFileToPage(plugin('example-plugin')->assets('assets/css/example.css'));
```

### 向页面添加脚本文件

```php
Hook::addScriptFileToPage(plugin('example-plugin')->assets('assets/js/example.js'), ['user']);
```

通过插件 `assets` 方法也可获取静态资源 URL。第一个参数为要加载的 URL；第二个参数可选，表示仅当请求的 URI 与参数匹配时才加载，可使用通配符。

### 注册 JavaScript 的语言文件

```php
Hook::registerPluginTransScripts('example-plugin');
```

> 注意：语言文件路径必须为插件目录下的 `lang/{Language}/locale.js`。

### 显示用户 badge （4.3.0+）

向用户中心和管理面板左上角的用户信息面板添加 badge：

```php
Hook::addUserBadge('text', 'green');
```

第一个参数为 badge 文本；第二个参数为颜色，可选，默认 `primary`。

### 发送通知 （4.3.0+）

```php
Hook::sendNotification($users, $title, $content);
```

`$users` 为接收通知的用户（5.0.0+ 可传入单个用户实例）。

### 注册中间件 （4.3.0+）

动态地向 Blessing Skin 现有路由增加中间件（**插件自己注册的路由不需要使用此 API**）：

```php
class Middleware
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}

Hook::pushMiddleware(Middleware::class);
```

> 注意：传入的必须是中间件本身，而非其实例。