# AGENTS.md - BlessingSkin OIDC 插件开发指南

本文档为 AI 编程助手提供代码库开发的规范和指南。

## 项目概述

这是一个 BlessingSkin 服务器的 OIDC 登录插件，支持通过 OIDC 协议（如 Casdoor、Keycloak）进行身份认证。

- **命名空间**: `Blessing\OAuth\OIDC`
- **PSR-4 自动加载**: `Blessing\OIDC\` → `src/`
- **最低 PHP 版本**: 7.4+（基于 Laravel 依赖）
- **目标平台**: BlessingSkin Server 5.x/6.x

## 项目结构

```
oauth-oidc/
├── bootstrap.php          # 插件入口，注册事件监听和配置
├── src/
│   ├── OIDCExtendSocialite.php  # Socialite 扩展注册
│   └── OIDCProvider.php         # OIDC OAuth2 提供者实现
├── lang/
│   ├── en/general.yml     # 英文语言包
│   └── zh_CN/general.yml  # 简体中文语言包
├── package.json           # 插件元信息
├── composer.json          # PHP 依赖
└── .env.example           # 环境配置示例
```

## 构建命令

### 发布打包

本项目使用 GitHub Actions 自动打包发布，无需手动构建。

手动打包命令（用于测试）：
```bash
# 打包为符合 BlessingSkin 插件规范的 zip
mkdir -p dist/oauth-oidc
cp -r bootstrap.php src lang package.json composer.json LICENSE readme.md dist/oauth-oidc/
cd dist && zip -r ../OAuth-OIDC.zip oauth-oidc
```

### 依赖安装

```bash
composer install --no-dev    # 生产环境
composer install             # 开发环境
```

## 测试命令

**注意**: 本项目当前没有测试框架。

如需添加测试，建议使用 PHPUnit：
```bash
# 安装测试依赖
composer require --dev phpunit/phpunit

# 运行所有测试
./vendor/bin/phpunit

# 运行单个测试文件
./vendor/bin/phpunit tests/OIDCProviderTest.php

# 运行单个测试方法
./vendor/bin/phpunit --filter testGetAuthUrl
```

## Lint/静态分析命令

**注意**: 本项目当前没有配置 Lint 工具。

建议使用以下工具进行代码质量检查：
```bash
# PHP-CS-Fixer (代码风格修复)
composer require --dev friendsofphp/php-cs-fixer
./vendor/bin/php-cs-fixer fix src/ --rules=@PSR12

# PHPStan (静态分析)
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse src/

# Psalm (静态分析)
composer require --dev vimeo/psalm
./vendor/bin/psalm
```

## 代码风格指南

### PHP 编码规范

遵循 **PSR-12** 编码规范：

- 文件必须使用 `<?php` 标签开头
- 文件编码必须为 UTF-8（无 BOM）
- 类名使用 PascalCase（如 `OIDCProvider`）
- 方法名使用 camelCase（如 `getUserByToken`）
- 常量使用 UPPER_SNAKE_CASE（如 `IDENTIFIER`）
- 属性使用 camelCase（如 `$scopes`）
- 缩进使用 4 个空格
- 类的开始括号必须另起一行

### 导入规范

```php
<?php

namespace Blessing\OAuth\OIDC;

// 导入顺序：标准库 → 第三方库 → 项目内部类
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class OIDCProvider extends AbstractProvider
{
    // ...
}
```

### 命名约定

| 类型 | 规范 | 示例 |
|------|------|------|
| 类名 | PascalCase | `OIDCProvider` |
| 接口 | PascalCase + Interface 后缀 | `ProviderInterface` |
| Trait | PascalCase | `HasOAuthConfig` |
| 方法 | camelCase | `getUserByToken` |
| 属性 | camelCase | `$clientId` |
| 常量 | UPPER_SNAKE_CASE | `IDENTIFIER` |
| 变量 | camelCase / snake_case | `$userInfo` |
| 环境变量 | UPPER_SNAKE_CASE | `OIDC_CLIENT_ID` |

### 类型声明

PHP 7.4+ 支持类型声明，建议使用：

```php
// 方法参数和返回值类型声明
protected function getUserByToken(string $token): array
{
    // ...
}

protected function mapUserToObject(array $user): User
{
    // ...
}

// 属性类型声明 (PHP 7.4+)
protected array $scopes = ['openid', 'profile', 'email'];
protected string $scopeSeparator = ' ';
```

### 注释规范

- 注释语言：**中文为主**，关键技术术语可保留英文
- 类和方法必须有文档注释
- 使用 PHPDoc 格式

```php
/**
 * OIDC OAuth2 提供者
 * 
 * 实现 OIDC 协议的用户认证流程
 */
class OIDCProvider extends AbstractProvider
{
    /**
     * 获取用户信息
     * 
     * @param string $token 访问令牌
     * @return array 用户信息数组
     */
    protected function getUserByToken(string $token): array
    {
        // 通过 Bearer Token 请求用户信息端点
        // ...
    }
}
```

### 错误处理

使用 Laravel 异常处理机制：

```php
use Exception;
use Illuminate\Support\Facades\Log;

try {
    $response = $this->getHttpClient()->get($url, $options);
} catch (Exception $e) {
    Log::error('OIDC 认证失败', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    throw $e;
}
```

## 配置管理

### 环境变量 (.env)

所有敏感配置必须通过 `.env` 文件注入：

```env
OIDC_CLIENT_ID=your-client-id
OIDC_CLIENT_SECRET=your-client-secret
OIDC_REDIRECT_URI=https://your-domain/auth/login/oidc/callback
OIDC_DISPLAY_NAME='OIDC'
OIDC_AUTHORIZE_URL=https://oidc-provider/authorize
OIDC_TOKEN_URL=https://oidc-provider/token
OIDC_USERINFO_URL=https://oidc-provider/userinfo
```

### 配置注册

在 `bootstrap.php` 中注册配置：

```php
config(['services.oidc' => [
    'client_id'     => env('OIDC_CLIENT_ID'),
    'client_secret' => env('OIDC_CLIENT_SECRET'),
    'redirect'      => env('OIDC_REDIRECT_URI'),
    'authorize_url' => env('OIDC_AUTHORIZE_URL'),
    'token_url'     => env('OIDC_TOKEN_URL'),
    'userinfo_url'  => env('OIDC_USERINFO_URL'),
]]);
```

## 国际化 (i18n)

语言文件位于 `lang/` 目录，使用 YAML 格式：

```yaml
# lang/zh_CN/general.yml
title: 使用 OIDC 登录
description: 用 OIDC 账号来登录皮肤站
```

使用翻译：
```php
trans('Blessing\OAuth\OIDC::general.title')
```

## Git 工作流

### 提交信息规范

使用约定式提交：

```
<type>(<scope>): <subject>

[optional body]
```

类型（type）：
- `feat`: 新功能
- `fix`: 修复 bug
- `docs`: 文档更新
- `style`: 代码格式调整
- `refactor`: 重构
- `test`: 测试相关
- `chore`: 构建/工具变更

示例：
```
feat(provider): 添加 logout 端点支持
fix(auth): 修复 token 过期处理逻辑
docs(readme): 更新配置说明
```

### 分支规范

- `main`: 主分支，稳定版本
- `develop`: 开发分支
- `feature/*`: 功能分支
- `fix/*`: 修复分支

## 发布流程

1. 更新 `package.json` 中的版本号
2. 更新 `readme.md` 中的变更日志
3. 创建 Git 标签: `git tag v1.x.x`
4. 推送标签: `git push origin v1.x.x`
5. GitHub Actions 自动构建并上传 Release 资源

## 关键依赖

| 包名 | 版本 | 用途 |
|------|------|------|
| `socialiteproviders/manager` | ^4.0 | OAuth 管理器 |
| `socialiteproviders/genericoauth2` | ^4.1 | 通用 OAuth2 提供者 |
| `psr/log` | ^1 | 日志接口 |

## BlessingSkin 插件开发注意事项

1. 插件必须包含 `package.json` 和 `bootstrap.php`
2. 插件命名空间在 `package.json` 中定义
3. 使用 `Blessing\Filter` 添加过滤器
4. 使用 `Illuminate\Contracts\Events\Dispatcher` 监听事件
5. 配置通过 `.env` 注入，避免硬编码敏感信息

## 常见问题

### 调试 OIDC 流程

```php
// 在 bootstrap.php 中添加日志
use Illuminate\Support\Facades\Log;

Log::debug('OIDC 配置', config('services.oidc'));
```

### 测试回调端点

确保 OIDC Provider 配置的回调地址与 `OIDC_REDIRECT_URI` 一致：
```
https://your-blessingskin-domain/auth/login/oidc/callback
```
