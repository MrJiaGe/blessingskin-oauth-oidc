# 表单

> 来源：https://bs-plugin.netlify.app/guide/form.html

- [创建一个表单](#创建一个表单)
- [生成表单](#生成表单)
- [渲染表单](#渲染表单)
- [生命周期钩子](#生命周期钩子)
    - [表单渲染前](#表单渲染前)
    - [表单处理 POST 请求前](#表单处理-post-请求前)
    - [表单处理 POST 请求后](#表单处理-post-请求后)
- [添加消息](#添加消息)
- [添加通告 5.0.0+](#添加通告-badge-text-5-0-0)
- [添加按钮](#添加按钮)
- [传递数据](#传递数据)
- [设置表单边框类型](#设置表单边框类型)
- [添加控件](#添加控件)
    - [文本输入框](#文本输入框)
    - [复选框](#复选框)
    - [文本域](#文本域)
    - [下拉框](#下拉框)
    - [文本框组](#文本框组)
- [控件共有的方法](#控件共有的方法)
    - [value 方法](#value-方法)
    - [hint 方法](#hint-方法)
    - [format 方法](#format-方法)
    - [disabled 方法](#disabled-方法)
    - [description 方法](#description-方法)

Blessing Skin 使用了 AdminLTE 作为 UI 框架。`OptionForm` 和 `OptionFormItem` 类可以快速生成 AdminLTE 风格的表单。

- `OptionForm`：生成表单
- `OptionFormItem`：生成表单项

## 创建一个表单

可在控制器中创建表单后渲染传递给视图，也可在视图中直接嵌入 PHP 代码。

调用 `Option` Facade 的 `form` 方法：

- 第一个参数：表单名称（唯一）
- 第二个参数：标题（可重复），可用 `OptionForm::AUTO_DETECT` 自动从语言文件寻找
- 第三个参数：回调函数（接收 `OptionForm` 实例）

```php
$form = Option::form('my_form', '表单示例', function ($form) {
    //
});
```

## 生成表单

```php
$form->handle();
```

> 只有调用 `handle` 才能让表单接收 POST 请求来响应数据更改。

## 渲染表单

多种渲染方法，但最终必须调用 `render` 生成 HTML：

```php
{!! $form->render() !!}
```

> `render` 返回 HTML，所以要避免 Blade 模板引擎的转义：双花括号 `{{ }}` 会转义，`{!! !!}` 不会。

可选的渲染选项：

```php
$form->renderWithoutTable();        // 不使用 <table> 排版
$form->renderInputTagsOnly();       // 只显示输入框，不显示左边标题
$form->renderWithoutSubmitButton(); // 取消「提交」按钮渲染
```

## 生命周期钩子

### 表单渲染前

```php
$form->always(function ($form) {
    // 渲染前执行
});
```

### 表单处理 POST 请求前

```php
$form->before(function ($form) {
    // 处理 POST 前执行
});
```

### 表单处理 POST 请求后

```php
$form->after(function ($form) {
    // 处理 POST 后执行
});
```

## 添加消息

出现在表单顶部：

```php
$form->addMessage('写文档不容易啊');
$form->addMessage('写文档不容易啊', 'success');
$form->addMessage('写文档不容易啊', 'warning');
$form->addMessage('写文档不容易啊', 'danger');
```

## 添加通告 （5.0.0+）

类似 `addMessage`，出现在表单顶部：

```php
$form->addAlert('这是文字');
```

默认类型 `info`。

## 添加按钮

除「提交」外的按钮，出现在「提交」旁：

```php
$form->addButton([
    'style' => 'primary',
    'text'  => '点我'
]);
```

支持选项：

- `style`：`default` / `primary` / `success` / `info` / `danger` / `warning`，默认 `default`
- `class`：HTML classes，类型为数组
- `href`：指定则按钮渲染为 `<a>` 元素
- `text`：按钮文本
- `type`：按钮类型（仅在渲染为 `<button>` 时有效）
- `name`：按钮的 `name` 属性

## 传递数据

```php
$form->with('key', $value);
```

## 设置表单边框类型

```php
$form->type('info');      // info / success / warning / danger / default
```

## 添加控件

控件方法的第一个参数是它在 `options` 表中的键；第二个参数是显示的标题（可用 `OptionForm::AUTO_DETECT` 自动翻译）。

代码应写在 `Option::form` 的回调函数中：

```php
$form = Option::form('my_form', '表单', function ($form) {
    $form->text(/* ... */);
});
```

### 文本输入框

```php
$form->text('my_text', '文本输入框');
$form->text('my_text', '文本输入框')->placeholder('占位符');
```

### 复选框

```php
$form->checkbox('my_checkbox', '复选框');
$form->checkbox('my_checkbox', '复选框')->label('标签');
```

### 文本域

```php
$form->textarea('my_textarea', '文本域');
$form->textarea('my_textarea', '文本域')->rows(50);
```

### 下拉框

```php
$form->select('my_select', '下拉框')
    ->option('option1', '选项1')
    ->option('option2', '选项2');
```

- 第一个参数：保存到数据库的值
- 第二个参数：页面显示的文本

### 文本框组

```php
$form->group('my_group', '文本框组')
    ->text('input1')
    ->addon('至')
    ->text('input2');
```

`text` 和 `addon` 可任意数量搭配。

## 控件共有的方法

### `value` 方法

预先设置控件的值：

```php
$form->text('t', '文本框')->value('预先设置的值');
```

### `hint` 方法

在表单项标题右边增加问号图标，悬浮有气泡提示：

```php
$form->text('t', '文本框')->hint('您可能不知道……');
```

### `format` 方法

处理用户输入的数据，回调函数返回处理后的值（再保存到数据库）：

```php
$form->text('t', '文本框')
    ->format(function ($data) {
        // $data 是这个表单项的值
    });
```

### `disabled` 方法

```php
$form->text('t', '文本框')->disabled();
```

### `description` 方法

在控件下方增加一行描述（不会被转义）：

```php
$form->text('t', '文本框')->description('这是一行描述');
```