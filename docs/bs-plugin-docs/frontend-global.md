# 全局 `blessing` 变量

> 来源：https://bs-plugin.netlify.app/guide/frontend-global.html

- [概览](#概览)
- [base_url](#base-url)
- [debug](#debug)
- [env](#env)
- [fallback_locale](#fallback-locale)（v5 起移除）
- [locale](#locale)
- [site_name](#site_name)
- [timezone](#timezone)
- [version](#version)
- [route 4.0.0+](#route-badge-text-4-0-0)
- [extra 4.0.0+](#extra-badge-text-4-0-0)
- [i18n 4.0.0+](#i18n-badge-text-4-0-0)
- [fetch 4.0.0+](#fetch-badge-text-4-0-0)
- [event 4.0.0+](#event-badge-text-4-0-0)
- [notify 4.0.0+](#notify-badge-text-4-0-0)

## 概览

`blessing` 是挂载在 `window` 上的全局变量，是一个对象。

## `base_url`

类型：`string`

当前 Blessing Skin 的根 URL。如果安装在子目录中，也包含子目录名称。

## `debug`

类型：`boolean`

当前是否处于调试状态。

## `env`

类型：`string`

等同于 `APP_ENV`。

## `fallback_locale`

类型：`string`

> ⚠️ v5.0.0 起此属性已被移除。

## `locale`

类型：`string`

当前环境的语种。

## `site_name`

类型：`string`

站点名称。

## `timezone`

类型：`string`

服务器所在时区，由 `php.ini` 设置决定。

## `version`

类型：`string`

当前 Blessing Skin 的版本。

## `route` （4.0.0+）

类型：`string`

当前请求的 Laravel 路由。

## `extra` （4.0.0+）

类型：`any`

由后端向前端通过 Blade 生成 JavaScript 代码传递的数据。不同页面有不同的数据。

## `i18n` （4.0.0+）

类型：`object`

前端国际化的文本内容。

## `fetch` （4.0.0+）

类型：`{ [method: string]: (url: string, data?: object) => Promise<object> }`

Blessing Skin 的前端资源请求方法，详见 [前端网络请求](fetch.md)。

## `event` （4.0.0+）

类型：`{ on(eventName: string, listener: Function): void; emit(eventName: string, payload?: object): void }`

前端事件系统，详见 [事件监听（前端）](frontend-event.md)。

## `notify` （4.0.0+）

类型：`{ [key: string]: Function }`

目前 `blessing.notify` 中的方法：

- `showModal`（v5 起用法完全改变）