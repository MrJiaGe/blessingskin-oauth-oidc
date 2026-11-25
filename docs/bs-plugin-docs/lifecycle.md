# 生命周期钩子

> 来源：https://bs-plugin.netlify.app/guide/lifecycle.html

Blessing Skin 为插件提供了三个生命周期钩子，用于进行准备或清理操作。所有使用钩子的代码都必须以数组的形式定义在插件根目录的 `callbacks.php` 文件里。

## `callbacks.php`

返回键值对数组。键是生命周期事件的类，值是回调函数。回调函数参数中可使用类型注解，Blessing Skin 会用 Laravel 的服务容器解析依赖并注入：

```php
return [
    App\Events\PluginWasEnabled::class => function (App\Services\PluginManager $manager) {
        // $manager 就是 App\Services\PluginManager 实例
    }
];
```

## 插件被启用

事件：`App\Events\PluginWasEnabled`

利用此钩子可进行初始化工作，例如检查环境、向数据库创建数据表（**记得先检查数据表是否已存在，重复建表会报错**）。

## 插件被关闭

事件：`App\Events\PluginWasDisabled`

## 插件即将被删除

事件：`App\Events\PluginWasDeleted`

管理员在「插件管理」执行删除操作后，插件尚未被删除，会先触发该事件（命名有误导，实际只是删除信号）。等到事件触发完成后，插件才被真正删除。

> **实践角度**：插件被启用时创建了什么，并不意味着在删除时就要清理什么。例如验证用户信息的插件，删除时把数据表也删掉，下次启用时用户就要重新验证。