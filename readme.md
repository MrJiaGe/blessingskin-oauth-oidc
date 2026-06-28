# BlessingSkin OIDC 插件

[blessingskin-oauth-oidc](https://github.com/MrJiaGe/blessingskin-oauth-oidc)是一个为 [BlessingSkin](https://github.com/bs-community/blessing-skin-server) 提供 **OIDC 登录支持** 的插件。  
需Oauth核心已安装且运行  
目前已在 **BlessingSkin 6.0.2** 测试可用。

---

## 功能特性

- 支持 OIDC 协议的第三方身份认证  
- 可用于 [Casdoor](https://casdoor.org/)、Keycloak 等身份提供服务  
- 无需修改 BlessingSkin 源码，安装插件即可使用
- **每次登录自动同步用户名和邮箱**：从 OIDC Provider 获取最新用户信息并同步到本地
- **基于 OpenID (sub) 绑定**：使用 OIDC subject 标识符绑定用户，避免邮箱变更导致的账户问题
- **支持多 Provider**：通过 `OIDC_ISSUER` 配置支持多个 OIDC Provider
- **账号关联选择**：首次登录时可选择关联现有账户或创建新账户（可配置）

---

## 安装方法

1. 前往 **管理面板 → 插件管理 → 上传插件** 进行安装。  

2. 插件安装包可在 [Releases](../../releases) 获取，或者下载整个仓库后，将 `oauth-oidc` 文件夹压缩为 zip（注意 **压缩包里必须包含外层 `oauth-oidc` 文件夹**），再上传安装。
目录结构应当类似：  
```目录示例
zip文件.zip  
└── oauth-oidc  
    ├── LICENSE  
    ├── bootstrap.php  
    ├── callbacks.php  
    ├── composer.json  
    ├── lang  
    │   ├── en  
    │   │   └── general.yml  
    │   └── zh_CN  
    │       └── general.yml  
    ├── package.json  
    ├── readme.md  
    ├── resources  
    │   └── views  
    │       └── link-choice.twig  
    └── src  
        ├── OIDCAuthController.php  
        ├── OIDCExtendSocialite.php  
        ├── OIDCLinkController.php  
        ├── OIDCProvider.php  
        ├── OIDCSession.php  
        └── OIDCUserBinding.php
```

⚠️ 如果插件未生效：  
- 请检查上传的 zip 包结构是否正确（应为 `oauth-oidc/` 文件夹而不是直接散落的文件）。  
- 如果使用了 CDN，尝试刷新缓存。  

---

## 配置示例（以 Casdoor 为例）

1. 在 Casdoor 后台新建一个应用，并设置回调地址为：  

https://your-blessingskin-domain/oidc/callback  

2. 正确配置应用程序并获取 Casdoor 提供的 **Client ID**、**Client Secret**。  

3. 在 BlessingSkin .env文件中，配置以下信息（这是示例，别直接抄，记得改改😅）：  

- OIDC_CLIENT_ID=your-client-id  
- OIDC_CLIENT_SECRET=your-client-secret  
- OIDC_REDIRECT_URI=https://your-blessingskin-domain/oidc/callback  
- OIDC_DISPLAY_NAME='OIDC'  
- OIDC_AUTHORIZE_URL=https://your-oidc-domain/login/oauth/authorize  
- OIDC_TOKEN_URL=https://your-oidc-domain/api/login/oauth/access_token  
- OIDC_USERINFO_URL=https://your-oidc-domain/api/userinfo
- OIDC_LINK_ENABLED=true  # 可选，启用账号关联选择功能（默认 true）
- OIDC_ISSUER=  

### 多 Provider 支持

如果需要使用多个 OIDC Provider，配置 `OIDC_ISSUER` 以区分不同 Provider 的用户：

```env
OIDC_ISSUER=https://casdoor.example.com
```

单 Provider 场景可留空。

以Casdoor为例，最后三个参数可访问Provider域名+后缀.well-known/openid-configuration获取  

如果遇到问题可以将.well-known/openid-configuration地址附带.env.example文件发给大模型咨询  

保存即可完成绑定。  

---

## 已知问题

无。

---

## 历史问题（已修复）

- ~~OIDC 登录关联已有账号后，退出登录再次使用 OIDC 登录时仍要求关联账号~~（v1.2.0 已修复）
- ~~关联流程中登录页面显示 OIDC 登录选项~~（v1.2.0 已修复）
- ~~`UserLoggedIn` 事件监听失败导致绑定流程中断~~（v1.2.0 已修复）

---

## 用户绑定机制

本插件使用 OIDC `sub` claim（用户唯一标识符）绑定 BlessingSkin 用户：

1. **首次登录**：通过邮箱查找或创建用户，同时创建 `sub` → `uid` 绑定记录
2. **后续登录**：通过 `sub` 查找绑定记录，直接获取关联用户
3. **邮箱变更**：不影响用户关联，仍通过 `sub` 识别用户
4. **信息同步**：每次登录同步用户名和邮箱

### 绑定流程

```
OIDC 回调 → 查 bindings 表
  ├─ 有映射 → 直接登录
  └─ 无映射 → 查邮箱
       ├─ 邮箱存在 → 选择页 A
       │   ├─ [登录并关联] → autoLink() → 自动绑定 + 自动登录
       │   └─ [使用其他账户] → 普通登录页(无 OIDC)
       └─ 邮箱不存在 → 选择页 B
           ├─ [创建新账户] → createNewAccount() → 注册 + 绑定 + 登录
           └─ [关联现有账户] → 普通登录页(无 OIDC)
```

### 账号关联选择

当 `OIDC_LINK_ENABLED=true`（默认）时，首次使用 OIDC 登录的用户会看到选择页面：

- **关联现有账户**：如果您已有 BlessingSkin 账户，可以登录后进行关联
- **创建新账户**：创建一个全新的账户，并使用 OIDC 登录

创建新账户后，用户的密码为空，建议在个人设置中设置密码以便使用邮箱登录。

若不需要此功能，可设置 `OIDC_LINK_ENABLED=false`，系统将自动通过邮箱查找或创建用户。

### 数据库表

插件启用时会自动创建 `oidc_user_bindings` 表存储绑定关系：

| 字段 | 说明 |
|------|------|
| `uid` | BlessingSkin 用户 ID |
| `oidc_sub` | OIDC subject 标识符 |
| `oidc_issuer` | OIDC Issuer URL（多 Provider 场景） |

## 致谢

- [BlessingSkin](https://github.com/bs-community/blessing-skin-server)  
- [Casdoor](https://github.com/casdoor/casdoor)  
- OIDC 协议相关社区  

---

## 开源协议

本插件基于 **MIT 协议** 开源，欢迎二次开发与贡献（当然，核心功能已经实现了，如果没有bug的话我就不再修改了）。
