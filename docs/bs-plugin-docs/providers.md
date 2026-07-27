# 服务提供者

> 来源：https://bs-plugin.netlify.app/guide/providers.html
>
> 💡 此特性需要 5.0.0 版本或以上。

> ⚠️ **注**：原始文档页面内容较简略，详细内容请参考 Laravel 官方文档 [服务提供者](https://learnku.com/docs/laravel/5.8/providers)。

## 概述

Blessing Skin v5 允许插件通过 `package.json` 的 `enchants.providers` 字段声明要加载的 Service Provider。可省略命名空间（从插件命名空间加载），也可省略类名后的 `ServiceProvider`：

```json
{
  "namespace": "Example",
  "enchants": {
    "providers": [
      "Example\\MyServiceProvider",
      "Our"
    ]
  }
}
```

`"Our"` 会被自动补全为 `Example\\OurServiceProvider`。

## 为什么用服务提供者

- 在服务提供者中执行的代码 **不会被 Blessing Skin 异常捕获**（而 `bootstrap.php` 中的异常会被捕获）。
- 服务提供者中通常进行以下操作：
    - 注册容器绑定
    - 注册中间件
    - 注册事件
    - 注册路由
    - 发布资源（asset publishing）

具体写法请参考 Laravel 服务提供者文档。