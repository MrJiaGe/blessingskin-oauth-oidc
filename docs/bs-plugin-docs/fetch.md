# 前端网络请求

> 来源：https://bs-plugin.netlify.app/guide/fetch.html
>
> ⚠️ 本节内容仅适用于 Blessing Skin v4

- [从 v3 迁移到 v4](#从-v3-迁移到-v4)
- [使用 `blessing.fetch`](#使用-blessing-fetch)
- [API](#api)
- [事件](#事件)

## 从 v3 迁移到 v4

### 代码迁移

v3 中所有 AJAX 通过 `fetch` 函数（实际上是 `$.ajax` 包装）。v4 起使用浏览器原生 `fetch`。

迁移方案：

1. **临时方案**：将 `fetch` 换成 `$.ajax`。但 `$.ajax` 返回的不是真正的 `Promise`，需用 `Promise.resolve()` 包装，且无法享受 `blessing.fetch` 的便利特性。
2. **推荐方案**：使用 `blessing.fetch`（见下文）。

### 请不要覆盖 `fetch` 函数

v4 使用浏览器原生 Fetch API，覆盖 `fetch` 会影响 Blessing Skin 本身的运行。**绝对不允许**。

若需修改请求数据，请监听 `beforeFetch` 事件：

```js
blessing.event.on('beforeFetch', request => {
    request.data.invitationCode = $('#invitation-code').val()
})
```

## 使用 `blessing.fetch`

`blessing.fetch` 中的所有方法都拥有以下特性，开箱即用。

### 基础 URL

只需填写相对路径。即使 Blessing Skin 安装在子目录也能正确处理。

### 异常处理

如果 HTTP 请求发生错误，Blessing Skin 会自动弹出模态报错对话框。

### CSRF Token

请求前自动加上，无需干预。

### 解析 JSON

后端响应 `Content-Type` 为 `application/json` 时会自动解析为 JS 对象，否则以文本返回。

## API

所有方法返回 `Promise`。

### `get`

`GET` 请求，参数为 `url` 和可选 `params`：

- `url`：字符串，相对 URL
- `params`：JS 对象，会转成 query string

### `post`

`POST` 请求，参数为 `url` 和可选 `data`：

- `url`：字符串，相对 URL
- `data`：`object` 或 `FormData`
    - JS 对象：序列化为 JSON
    - `FormData`：以 `FormData` 提交

## 事件

使用 `blessing.fetch` 时会触发事件。

### `beforeFetch`

**触发时机**：调用 Fetch API 之前。

参数对象属性：

- `method`：HTTP 动词，**不要修改**
- `url`：目标 URL（非完整），除非必要不要修改
- `data`：JS 对象（或 `FormData`，仅上传材质时）

### `fetchError`

**触发时机**：Fetch API 发生错误时（网络故障或非 2xx）。

> 即使监听了此事件，错误发生时模态警告对话框依然会出现。

参数可能是 `Error` 实例或字符串。

```js
blessing.event.on('fetchError', error => {
    $('#somewhere').text('数据暂时无法获取')
})
```