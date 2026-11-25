# 辅助函数

> 来源：https://bs-plugin.netlify.app/guide/helpers.html

Blessing Skin 包含一些全局可用的辅助 PHP 函数，本身也在使用；如果觉得方便，您可以在插件中自由使用。

- [json](#json)
- [option](#option)
- [option_localized](#option-localized)
- [plugin](#plugin)
- [plugin_assets](#plugin-assets)

## `json`

返回 JSON 响应。**需要在控制器中手动 `return`**：

```php
json(['a' => 1, 'b' => 2]);
// {"a":1,"b":2}

json('Hello.', 0, ['a' => 1]);
// {"code":0,"message":"Hello.","data":{"a":1}}

json('Success', 0);
// {"code":0,"message":"Success"}

json('Failed');
// {"code":1,"message":"Failed"}
```

> **注意**：v4.1.0 起，`json` 函数第二个参数起行为变更（`errno` → `code`，`msg` → `message`，第三个参数会被包在 `data` 字段下）。详见 [迁移指南](migration.md#从-4-0-0-迁移到-4-1-0)。

## `option`

返回或设置存储在数据库中的配置项：

```php
$value = option('my_custom_option');                       // 获取
$value = option('my_custom_option', 'default');            // 不存在则返回默认值
option(['my_custom_option' => 'Yeah']);                    // 设置
```

## `option_localized`

功能与 `option` 相同，返回值可根据当前语言环境而不同。

通过此函数设置不同语言对应不同值时，需要先切换到目标语言：

```php
// 假定 `greeting` 在中文为「你好」，英文为 "hello"
// en
option_localized('greeting');   // hello
// zh_CN
option_localized('greeting');   // 你好
```

## `plugin`

返回指定插件的实例：

```php
$plugin = plugin('example-plugin');
```

## `plugin_assets`

返回插件资源文件的 URL：

```php
$url = plugin_assets('example-plugin', 'assets/js/example1.js');
```