# 使用 Composer

> 来源：https://bs-plugin.netlify.app/guide/composer.html
>
> ⚠️ 此特性需要 Blessing Skin v3.5.0 或更高版本

有些时候插件需要一些 PHP 库。通常使用 Composer 来安装。

Composer 会把依赖下载到 `vendor` 目录，Blessing Skin 会自动扫描并加载 `vendor` 目录，不需要任何配置。

## 用法

在插件目录下执行 composer 命令：

```bash
composer require vendor/package
```

尽管 Composer 会生成 `composer.json` 和 `composer.lock`，但 Blessing Skin 不会用到这些文件。

接着在插件代码中正常使用即可。

> ⚠️ **打包发布或共享时，务必将 `vendor` 目录打包进去**，否则插件将无法运行。