# BlessingSkin OIDC 插件

为 [BlessingSkin](https://github.com/bs-community/blessing-skin-server) 提供 **OIDC 登录支持**的插件，支持 Casdoor、Keycloak 等身份提供商。已在 BlessingSkin 6.0.2 测试可用。

---

## 功能特性

- **OIDC 身份认证**：基于 OpenID Connect 协议的第三方登录
- **自动同步用户信息**：每次登录同步用户名和邮箱
- **基于 sub 绑定**：使用 OIDC subject 标识符绑定用户，邮箱变更不影响关联
- **多 Provider 支持**：通过 `OIDC_ISSUER` 配置支持多个身份提供商
- **账号关联选择**：首次登录可选择关联现有账户或创建新账户
- **管理面板**：可在后台查看和管理用户绑定记录（编辑/删除/分页查询）

---

## 安装方法

1. **管理面板 → 插件管理 → 上传插件**，安装插件包。
2. 插件包可在 [Releases](../../releases) 下载，或手动打包：
   ```bash
   # 确保压缩包结构为：oauth-oidc/
   zip -r OAuth-OIDC.zip oauth-oidc/
   ```

⚠️ **注意**：zip 包根目录必须包含 `oauth-oidc/` 文件夹，而非直接散落的文件。

---

## 配置示例（Casdoor）

在 BlessingSkin `.env` 中配置：

```env
OIDC_CLIENT_ID=your-client-id
OIDC_CLIENT_SECRET=your-client-secret
OIDC_REDIRECT_URI=https://your-domain/oidc/callback
OIDC_DISPLAY_NAME='OIDC'
OIDC_AUTHORIZE_URL=https://casdoor.example.com/login/oauth/authorize
OIDC_TOKEN_URL=https://casdoor.example.com/api/login/oauth/access_token
OIDC_USERINFO_URL=https://casdoor.example.com/api/userinfo
OIDC_ISSUER=  # 注意：多 Provider 时必填
OIDC_LINK_ENABLED=true  # 是否启用账号关联选择（默认 true）
```

**获取端点地址**：访问您身份提供商（IDP）的 URL `https://your-provider/.well-known/openid-configuration` 查看完整配置。

---

## 管理面板

安装后在后台新增 **OIDC → 绑定管理** 菜单，支持：

- **列表查询**：查看所有绑定记录
- **字段编辑**：在线编辑 SUB 或 Issuer
- **删除绑定**：快速删除记录
- **分页浏览**：支持 20/50/100/500/全部分页，不会阻塞数据库查询


---

## 用户绑定机制

1. **首次登录**：通过邮箱查找或创建用户，同时创建 `sub` → `uid` 绑定
2. **后续登录**：通过 `sub` 直接查找绑定，获取关联用户
3. **邮箱变更**：不影响绑定关系，仍通过 `sub` 识别用户
4. **账号关联选择**：首次登录时可选择关联现有账户或创建新账户

---

## 数据库表

插件自动创建 `oidc_user_bindings` 表：

| 字段 | 说明 |
|------|------|
| `uid` | BlessingSkin 用户 ID |
| `oidc_sub` | OIDC subject 标识符 |
| `oidc_issuer` | OIDC Issuer URL |

## 致谢

- [BlessingSkin](https://github.com/bs-community/blessing-skin-server)  
- [Casdoor](https://github.com/casdoor/casdoor)  
- OIDC 协议相关社区  

---

## 开源协议

MIT 协议，欢迎二次开发与贡献。