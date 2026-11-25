# 数据库

> 来源：https://bs-plugin.netlify.app/guide/database.html

## 创建数据表

在插件被启用后创建数据表（详见 [生命周期钩子](lifecycle.md)）。使用 `Schema` Facade，不必在 `callbacks.php` 或 `bootstrap.php` 中写 `use Schema;`。

`create` 方法接收两个参数：

- 第一个：表名（无需手动加数据表前缀，Laravel 会自动加上）
- 第二个：回调函数（参数为 `Illuminate\Database\Schema\Blueprint`）

```php
Schema::create('my_table', function ($table) {
    // $table 参数上有很多实例方法
});
```

字段定义通过调用 `$table` 上的实例方法进行。详见 [Laravel 文档](https://laravel-china.org/docs/laravel/5.6/migrations/1400#3c6c8e)。

**创建前一定先检查数据表是否已存在**：

```php
if (! Schema::hasTable('my_table')) {
    Schema::create('my_table', function ($table) {});
}
```

## 通过 `DB` Facade 来操作数据库

Laravel 提供 `DB` Facade 进行底层数据库操作，需要对数据库操作有一定了解。详见 [Laravel 文档](https://laravel-china.org/docs/laravel/5.6/database/1397)。

## 通过 Eloquent ORM 来操作数据库

如果对数据库操作比较陌生，可使用 Eloquent ORM。通过 Eloquent 不需要手动操作数据库（自己创建数据表仍需通过 `Schema`），定义好数据模型即可使用。详见 [Laravel 文档](https://laravel-china.org/docs/laravel/5.6/eloquent/1403)。