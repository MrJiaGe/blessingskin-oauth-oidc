# 视图

> 来源：https://bs-plugin.netlify.app/guide/view.html

- [添加视图文件](#添加视图文件)
- [使用视图](#使用视图)
- [传递数据](#传递数据)
- [模板继承](#模板继承)

视图即 HTML，使用 Laravel 的 Blade 或 Twig 模板引擎。从 Blessing Skin v5 起开始使用 Twig。

## 添加视图文件

所有视图文件都放在插件目录下的 `views` 目录下。后缀名决定模板引擎：

- `.blade.php`：Blade
- `.twig`：Twig（v5 推荐）

## 使用视图

在控制器中使用全局辅助函数 `view`：

```php
public function showLoginPage()
{
    return view('Author\MyPlugin::hello.world');
}
```

注意必须指定命名空间。小数点 `.` 表示目录层级，上述示例对应 `views/hello/world.blade.php`。

## 传递数据

```php
return view('Author\MyPlugin::hello.world')->with('key', $value);
```

传递关联数组更优雅：

```php
return view('Author\MyPlugin::hello.world', ['name' => 'Secret', 'phone' => 2333]);
```

## 模板继承

为使插件页面与皮肤站风格一致，应使用模板继承。视图文件（以 Twig 为例）：

```twig
{% extends '要继承的母页面' %}

{% block title %}页面标题{% endblock %}

{% block content %}
  <!-- 页面内容 -->
{% endblock %}
```

可继承的模板：

- `user.base`：用户中心基本页面
- `admin.master`：管理面板基本页面

也可以自己定义一个视图后继承（注意命名空间）。