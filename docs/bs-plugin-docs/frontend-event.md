# 事件监听（前端）

> 来源：https://bs-plugin.netlify.app/guide/frontend-event.html
>
> ⚠️ 本节内容仅适用于 Blessing Skin v4 或更高版本

## 简介

从 v4 起前端提供轻量级事件系统，方便插件以低侵入方式实现一些操作。所有方法都在 `blessing.event` 下。

## 监听事件

`blessing.event.on(name, listener)`，第一个参数为字符串或 Symbol 表示事件名，第二个参数为回调函数：

```js
blessing.event.on('mounted', () => {
    // ...
})
```

同一事件可被监听多次，触发时所有回调都会执行。

## 触发事件

`blessing.event.emit(name, payload)`：

```js
blessing.event.emit('eventA', data)  // data 已定义
```

> 推荐传递 JavaScript 对象，便于将来扩展。如希望只读可传 `Object.freeze(data)`（但只做一层处理，嵌套属性仍可修改）。

## 所有可用的事件

### `beforeFetch`

**触发时机**：调用 Fetch API 之前。详见 [前端网络请求](fetch.md#事件)。

参数对象属性：

- `method`：HTTP 动词
- `url`：相对 URL
- `data`：`object | FormData`

### `fetchError`

**触发时机**：Fetch API 发生错误时（网络故障或非 2xx）。

参数对象属性：

- `message`：错误消息
- `response`：`Response | undefined`

> 这个事件传递给回调的参数永远是 `Error` 实例。

### `mounted`

**触发时机**：Vue.js 渲染的内容已挂载到页面后。若无 Vue 渲染内容则不触发。

**此事件不含任何参数**（v5 起变更）。