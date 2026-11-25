# 持久化配置

> 来源：https://bs-plugin.netlify.app/guide/option.html

插件可能需要存储一些配置信息。Blessing Skin 本身的配置都保存在数据库的 `options` 表中。

> 不建议直接读取 `options` 数据表，而应使用 `Option` Facade 或 `option` 辅助函数。

## 应用

假设插件需要从外部 URL 获取信息，URL 可被用户修改。我们设置一个配置项 `data_source`，可通过 `Option` Facade 或 `option` 辅助函数获取或设置。

> 设置配置项时若不存在将被自动添加，无需手动修改数据库。

## 使用 Facade

```php
use Option;

// 获取
Option::get('data_source');

// 允许返回默认值
Option::get('data_source', 'http://xxx/');

// 更新
Option::set('data_source', 'http://xxx/');
```

## 使用辅助函数

辅助函数全局可用，使用前无需导入，更简单。

详见 [`option` 函数](helpers.md#option) 和 [`option_localized` 函数](helpers.md#option-localized)。