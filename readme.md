# BlessingSkin OIDC 插件

这是一个为 [Blessing Skin](https://github.com/bs-community/blessing-skin-server) 提供 **OIDC 登录支持** 的插件。  
需Oauth核心已安装且运行  
目前已在 **Blessing Skin 6.0.2**（截至现在的最新版）测试可用。

---

## 功能特性

- 支持 OIDC 协议的第三方身份认证  
- 可用于 [Casdoor](https://casdoor.org/)、Keycloak 等身份服务  
- 无需修改 BlessingSkin 源码，安装插件即可使用  

---

## 安装方法

1. 前往 **管理面板 → 插件管理 → 上传插件** 进行安装。  

2. 插件安装包可在 [Releases](../../releases) 获取，或者下载整个仓库后，将 `oauth-oidc` 文件夹压缩为 zip（注意 **压缩包里必须包含外层 `oauth-oidc` 文件夹**），再上传安装。  

⚠️ 如果插件未生效：  
- 请检查上传的 zip 包结构是否正确（应为 `oauth-oidc/` 文件夹而不是直接散落的文件）。  
- 如果使用了 CDN，尝试刷新缓存。  

---

## 配置示例（以 Casdoor 为例）

1. 在 Casdoor 后台新建一个应用，并设置回调地址为：  

https://your-blessingskin-domain/auth/login/oidc/callback  

2. 获取 Casdoor 提供的 **Client ID**、**Client Secret**、**Issuer URL**。  

3. 在 BlessingSkin .env文件 → 插件设置中，填入以下信息：  

- OIDC_CLIENT_ID=your-client-id  
- OIDC_CLIENT_SECRET=your-client-secret  
- OIDC_REDIRECT_URI=https://your-blessingskin-domain/auth/login/oidc/callback  
- OIDC_display_Name='OIDC'  
- OIDC_AUTHORIZE_URL=https://your-oidc-domain/login/oauth/authorize  
- OIDC_TOKEN_URL=https://your-oidc-domain/api/login/oauth/access_token  
- OIDC_USERINFO_URL=https://your-oidc-domain/api/userinfo  


以Casdoor为例，最后三个参数可访问Provider域名+后缀.well-known/openid-configuration获取  

如果遇到问题可以将.well-known/openid-configuration地址附带.env.example文件发给大模型咨询  

保存即可完成绑定。  

---

## 致谢

- [Blessing Skin](https://github.com/bs-community/blessing-skin-server)  
- [Casdoor](https://github.com/casdoor/casdoor)  
- OIDC 协议相关社区  

---

## 开源协议

本插件基于 **MIT 协议** 开源，欢迎二次开发与贡献（当然，如果没有bug的话我就不再修改了）。