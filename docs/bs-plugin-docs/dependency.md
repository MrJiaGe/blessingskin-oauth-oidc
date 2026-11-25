# 声明插件依赖

> 来源：https://bs-plugin.netlify.app/guide/dependency.html
>
> ⚠️ 此特性需要 Blessing Skin v3.5.0 或更高版本

- [在哪里声明？](#在哪里声明)
- [明确 Blessing Skin 的版本](#明确-blessing-skin-的版本)
- [指定 PHP 版本要求](#指定-php-版本要求)
- [声明对其它插件的依赖](#声明对其它插件的依赖)

## 在哪里声明？

在 `package.json` 中添加 `require` 字段，值是键值对：

- 键：依赖的名称
- 值：版本（支持语义化版本，内部使用 `composer/semver` 解析）

## 明确 Blessing Skin 的版本

**强烈建议声明**。插件系统从 v3.2.0 开始，未明确版本时启用插件会提示警告。

```json
{
  "require": {
    "blessing-skin-server": "^3.2.0"
  }
}
```

> ⚠️ 语义化版本下，`^3.2.0` 会阻止插件在 v4 及以上运行。若插件足够简单，可写 `*`：

```json
{
  "require": {
    "blessing-skin-server": "*"
  }
}
```

也可写 `^3.2.0 || ^4.0.0`（但与 `*` 效果不同）。

## 指定 PHP 版本要求

从 5.0.0 起可声明 PHP 版本要求。键声明为 `php`：

```json
{
  "require": {
    "php": "^7.4"
  }
}
```

不满足时插件将无法开启。

## 声明对其它插件的依赖

键为插件的 `name` 值（**不是插件显示名称**）。假设 `a` 插件依赖 `b` 插件的 `^1.0.0`：

```json
{
  "require": {
    "b": "^1.0.0"
  }
}
```

启用 `a` 时若 `b` 未启用或版本不符，则不能启用 `a`。