# WHMCS Email Verify

WHMCS 客户邮箱验证码插件：用户未验证邮箱不能登录，新客户在注册 / 下单前即可完成验证。

## 设计特性

- **未验证不可登录**：登录后若邮箱未验证会被立即登出并跳转到验证页，输入邮箱验证码后自动登录（基于 `CreateSsoToken`）并返回原页面（如结账页），购物车保留。
- **注册 / 下单前验证**：在注册页和结账页的邮箱输入框下方注入验证码组件，未注册用户也能先验证邮箱；前端拦截提交，服务端通过 `ClientDetailsValidation` / `ShoppingCartValidateCheckout` 强制校验，账户创建后自动标记为已验证。
- **复用 WHMCS 原生字段**：验证状态直接读写 `tblusers.email_verified_at`，不新建任何数据表，停用插件后状态保留。
- **验证码不落库**：验证码通过 WHMCS 内置的 TransientData 缓存保存（仅存哈希，到期自动失效），并与当前会话绑定。
- **防滥用**：同一邮箱发送冷却、单个验证码错误次数上限、单 IP 每小时发送上限，均可配置。
- **人机验证联动**：配合 [whmcs-cap-captcha](https://github.com/SideCloudGroup/whmcs-cap-captcha) 使用时，注册 / 结账页发送验证码前需先通过 Cap 人机验证（直接复用该插件的配置，无需重复填写）。
- **后台管理**：查看所有用户的验证状态，支持搜索、筛选、单个 / 批量设置，以及一键将所有未验证用户设为已验证。
- **全异步交互**：前后台均为原生 JS + `fetch`，无第三方依赖，兼容 twenty-one / six 主题。
- **可编辑邮件模板**：激活时创建「Email Verification Code」邮件模板（含中英文），可在 WHMCS 邮件模板中自定义，自动套用全局邮件页眉页脚。

## 环境要求

- WHMCS 8.x
- PHP 7.2+

## 安装

1. 将 `modules` 目录上传并合并到 WHMCS 根目录，最终路径为 `modules/addons/email_verify/`。
2. 后台进入 **System Settings → Addon Modules**，激活 **Email Verify**。
3. 点击 **Configure**，按需调整设置，并在 **Access Control** 中勾选可访问的管理员角色组。
4. 首次启用前，建议先在插件页面将存量用户设为已验证，否则所有未验证的老用户下次登录都需要验证邮箱。

## 设置项

| 设置 | 说明 |
| --- | --- |
| 禁止未验证用户登录 | 开启后未验证用户登录需先完成邮箱验证 |
| 注册 / 下单前验证 | 新客户在注册页和结账页需先验证邮箱 |
| 发送验证码前人机验证 | 需安装并配置 whmcs-cap-captcha，未配置时自动跳过 |
| 屏蔽 WHMCS 自带验证邮件 | 不再发送 WHMCS 原生的链接式验证邮件 |
| 验证后自动登录 | 验证成功后自动登录，关闭则返回登录页 |
| 验证码位数 / 有效期 / 发送冷却 / 最大尝试次数 / 单 IP 每小时上限 | 验证码与防滥用参数 |
| 客服联系人 / 客服链接 | 显示在验证页底部，留空则不显示 |

## 使用

- **管理用户验证状态**：后台 **Addons → Email Verify**，可搜索用户并单个或批量设置验证状态。
- **自定义邮件内容**：后台 **System Settings → Email Templates** 中编辑「Email Verification Code」模板，可用变量：`{$code}`、`{$minutes}`、`{$email}` 以及常用的公司信息变量。
- **配合人机验证**：安装 [whmcs-cap-captcha](https://github.com/SideCloudGroup/whmcs-cap-captcha) 并填写其 Cap 服务地址、站点 key 和密钥即可，本插件会自动识别。注意：同一浏览器存在后台登录会话时人机验证不会生效，请使用无痕窗口测试。
- **日志**：验证成功、管理员修改状态、发送失败等事件均记录在 WHMCS 活动日志中，以 `Email Verify:` 开头。
