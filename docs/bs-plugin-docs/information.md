# 插件信息定义（`package.json`）

> 来源：https://bs-plugin.netlify.app/guide/information.html

## 前言

由于此文件是 JSON 格式，您必须按照 JSON 的规范来写。**错误的 `package.json` 将导致插件无法运行和皮肤站的插件列表无法加载**。编写完成后可在 [BeJSON](http://www.bejson.com/) 校验。

## 字段总览

- `name`（必选）
- `version`
- `title`
- `description`
- `author`
- `namespace`（必选）
- `config`
- `url`
- `require`
- `enchants`

## 详细介绍

### `name`

插件的唯一标识符，推荐使用 kebab-case 命名法：`my-plugin`、`this-is-an-example`。

> **切记**：不同的 `name` 值代表不同的插件，所以千万不能把版本信息写在此处。Blessing Skin Server 会把它当成另一个插件。

### `version`

插件版本。**值类型必须是字符串**。无内容限定，建议使用数字版本如 `1.0`、`3.2.1`，不建议带字母 "v"。数字版本有利于插件市场进行版本对比。

### `title`

插件在「插件管理」页上显示的名称，支持任意 UTF-8 字符。此字段重复不影响各自运行，但会给用户带来麻烦。

### `description`

插件描述，长度适中就好。

### `author`

插件作者。

### `namespace`

插件的命名空间，不能重复。`src` 目录下的所有类都会被加载到此命名空间下。调用插件中定义的视图也必须加上此命名空间。

> 由于 JSON 和 PHP 的命名空间都使用反斜杠 `\`：JSON 字段中要用 `\\` 来表示 `\`。例如 `Foo\\Bar` 表示 PHP 中的 `Foo\Bar`。

### `config`

「插件配置」页面是哪个视图文件。如果不需要「插件配置」页则可省略。插件配置页面位于 `views/config.blade.php` 时可省略，Blessing Skin 能自动识别。

### `url`

插件的 URL，可以是简介页或开源仓库地址。不影响插件运行，可省略。

### `require`

声明插件依赖。详见 [声明依赖](dependency.md)。

### `enchants` （5.0.0+）

定义影响插件行为的信息，细分不同字段：

#### `providers`

类型：字符串数组。定义要加载的 [服务提供者](providers.md)。

可省略命名空间（从插件指定的命名空间加载），也可省略类名后的 `ServiceProvider`：

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

第二个 `"Our"` 会被自动补全为 `Example\\OurServiceProvider`。

#### `conflicts`

声明插件冲突，结构与 `require` 类似。详见 [声明插件冲突](conflicts.md)。

#### `config`

指定配置页面（不同于根 `config`）—— 这个字段指定类的类名而非视图文件。

声明后用户在「插件页面」点击「配置」时会执行该类的公有 `render` 方法（可看作控制器方法）。此字段优先级高于根 `config` 字段。

#### `icon`

指定插件在「插件管理」页中的图标。值是一个对象：

- `fa`：Font Awesome 图标名（不需要加 `fa-` 前缀）
- `bg`：背景颜色（暂时必须为 AdminLTE v3 支持的颜色名）