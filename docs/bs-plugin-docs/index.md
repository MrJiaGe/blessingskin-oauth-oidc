# Blessing Skin 插件开发文档（本地整理版）

> 来源：https://bs-plugin.netlify.app/
> 整理时间：2026-07-27
> 说明：本文档为本地整理版本，仅供插件开发参考，请以官方文档为准。

## 快速开始

通过模板，结合本文档，您就能快速进行插件开发。

## 质量保证

文档由 Blessing Skin 的活跃贡献者编写和维护。当 Blessing Skin 的更新影响插件时，本文档也会相应更新。

## 简洁而详尽

本文档尽量以简单的语言来叙述，但又同时保证关键细节不会被省略。

---

## 文档目录

### 入门
- [快速创建插件](scaffold.md) — 通过官方模板仓库快速创建
- [插件目录结构](structure.md) — 插件目录中各文件的含义
- [插件信息定义](information.md) — `package.json` 的所有字段
- [插件的开始](bootstrap.md) — `bootstrap.php` 入口文件

### 后端开发
- [视图](view.md) — Twig / Blade 模板
- [路由](route.md) — 路由注册与中间件
- [多语言](i18n.md) — YAML / JS 语言包
- [持久化配置](option.md) — `option()` / `Option::` Facade
- [表单](form.md) — OptionForm 快速构建表单
- [辅助函数](helpers.md) — `json` / `option` / `plugin` 等
- [数据与模型](models.md) — User / Player / Texture
- [用户认证](auth.md) — `auth()` 中间件
- [生命周期钩子](lifecycle.md) — `callbacks.php`
- [数据库](database.md) — Schema / Eloquent

### 前端开发
- [全局 blessing 变量](frontend-global.md) — `window.blessing`
- [事件监听（后端）](backend-event.md) — Laravel 事件系统
- [事件监听（前端）](frontend-event.md) — `blessing.event`
- [前端网络请求](fetch.md) — `blessing.fetch`

### 插件元数据
- [声明插件依赖](dependency.md) — `require` 字段
- [声明插件冲突](conflicts.md) — `enchants.conflicts`
- [使用 Composer](composer.md) — 第三方 PHP 库
- [服务提供者](providers.md) — Service Provider 注册

### 升级参考
- [迁移指南](migration.md) — 各版本之间的破坏性变更