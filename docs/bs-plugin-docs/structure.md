# 插件目录结构

> 来源：https://bs-plugin.netlify.app/guide/structure.html

## 总览

```
.
├── assets/         # CSS、JS、图片等静态资源
├── lang/           # 语言文件
├── src/            # PHP 代码
├── views/          # 视图文件
├── bootstrap.php   # 入口文件（必选）
├── callbacks.php   # 生命周期钩子
└── package.json    # 插件基本信息（必选）
```

> 除了 `bootstrap.php` 和 `package.json` 是必选的外，其它都是可选的。

## 含义与作用

### `assets` 目录

存放如 CSS、JavaScript 和图片等静态资源文件。`assets` 目录内的结构可随意。

### `lang` 目录

存放语言文件。如果插件是多语言的，请将语言文件放在这里。目前仅支持简体中文和英语，所以 `lang` 目录下应只存在 `en` 和 `zh_CN` 两个目录（分别代表英语和简体中文）。

### `src` 目录

包含插件的 PHP 代码。内部目录结构可随意，但 PHP 代码中的命名空间必须按照目录结构进行安排。

### `views` 目录

包含插件的视图文件。`.blade.php` 后缀使用 Blade 引擎，`.twig` 后缀使用 Twig 引擎（v5 起推荐 Twig）。

### `bootstrap.php` 文件

**可别把这个文件与 Twitter 前端框架 Bootstrap 联系在一起！** 它们之间没有任何关系。

这是整个插件的入口文件，插件被加载时首先加载。只要插件没有被禁用，这个文件就会被加载并执行。

### `callbacks.php` 文件

处理当插件被启用、禁用和卸载时要执行的代码。

### `package.json` 文件

记录插件的一些基本信息。