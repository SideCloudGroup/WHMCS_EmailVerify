<?php

$_ADDONLANG['module_desc'] = '要求用户通过邮箱验证码验证邮箱后才能登录、注册或下单。';
$_ADDONLANG['activated'] = 'Email Verify 已激活，请先检查下方设置再对客户启用。';
$_ADDONLANG['deactivated'] = 'Email Verify 已停用，WHMCS 中已保存的验证状态不受影响。';

$_ADDONLANG['cfg_block_login'] = '禁止未验证用户登录';
$_ADDONLANG['cfg_block_login_desc'] = '邮箱未验证的用户登录后会被登出，必须输入发送到邮箱的验证码后才能登录。';
$_ADDONLANG['cfg_require_on_register'] = '注册 / 下单前验证';
$_ADDONLANG['cfg_require_on_register_desc'] = '新客户在注册页和结账页必须先验证邮箱，之后才能创建账户。';
$_ADDONLANG['cfg_require_captcha'] = '发送验证码前人机验证';
$_ADDONLANG['cfg_require_captcha_desc'] = '注册 / 结账页发送验证码前需通过人机验证。使用站点当前的 Hybula/SideCloud 验证码驱动（Turnstile / Cap / hCaptcha）。未安装或未配置该 hook 时将拒绝发送。';
$_ADDONLANG['cfg_suppress_native_mail'] = '屏蔽 WHMCS 自带验证邮件';
$_ADDONLANG['cfg_suppress_native_mail_desc'] = '启用登录拦截时，不再发送 WHMCS 自带的链接式邮箱验证邮件。';
$_ADDONLANG['cfg_auto_login'] = '验证后自动登录';
$_ADDONLANG['cfg_auto_login_desc'] = '验证成功后自动登录（使用 CreateSsoToken API），否则返回登录页重新登录。';
$_ADDONLANG['cfg_code_length'] = '验证码位数';
$_ADDONLANG['cfg_code_length_desc'] = '验证码的数字位数。';
$_ADDONLANG['cfg_code_ttl'] = '验证码有效期';
$_ADDONLANG['cfg_code_ttl_desc'] = '分钟。';
$_ADDONLANG['cfg_send_cooldown'] = '发送冷却时间';
$_ADDONLANG['cfg_send_cooldown_desc'] = '同一邮箱两次发送验证码的最小间隔（秒）。';
$_ADDONLANG['cfg_max_attempts'] = '最大尝试次数';
$_ADDONLANG['cfg_max_attempts_desc'] = '单个验证码允许输错的次数，超过后验证码作废。';
$_ADDONLANG['cfg_ip_hourly_limit'] = '单 IP 每小时上限';
$_ADDONLANG['cfg_ip_hourly_limit_desc'] = '同一 IP 每小时最多发送的验证码数量，0 表示不限制。';

$_ADDONLANG['cfg_support_label'] = '客服联系人';
$_ADDONLANG['cfg_support_label_desc'] = '显示在验证页，供无法完成验证的用户联系。留空则不显示。';
$_ADDONLANG['cfg_support_url'] = '客服链接';
$_ADDONLANG['cfg_support_url_desc'] = '可选，支持 http(s)://、mailto: 或 tg:// 链接。';

$_ADDONLANG['page_title'] = '邮箱验证';
$_ADDONLANG['page_heading'] = '验证您的邮箱';
$_ADDONLANG['page_intro'] = '登录前请先验证邮箱 {email}，请输入我们发送到该邮箱的验证码。';
$_ADDONLANG['expired_heading'] = '验证会话已过期';
$_ADDONLANG['expired_text'] = '请重新登录以继续验证邮箱。';
$_ADDONLANG['btn_login'] = '登录';
$_ADDONLANG['use_other_account'] = '使用其他账户登录';
$_ADDONLANG['support_text'] = '如遇到任何问题无法完成验证，请联系 {contact}';
$_ADDONLANG['code_placeholder'] = '请输入 {length} 位验证码';
$_ADDONLANG['btn_send'] = '发送验证码';
$_ADDONLANG['btn_sending'] = '发送中…';
$_ADDONLANG['btn_captcha'] = '人机验证中…';
$_ADDONLANG['msg_captcha_failed'] = '人机验证失败，请稍后重试。';
$_ADDONLANG['btn_resend_in'] = '{seconds} 秒后重发';
$_ADDONLANG['btn_verify'] = '验证';
$_ADDONLANG['btn_verifying'] = '验证中…';

$_ADDONLANG['msg_sent'] = '验证码已发送至 {email}。';
$_ADDONLANG['msg_cooldown'] = '请等待 {seconds} 秒后再获取验证码。';
$_ADDONLANG['msg_rate_limited'] = '您的网络请求验证码过于频繁，请稍后再试。';
$_ADDONLANG['msg_send_failed'] = '验证邮件发送失败，请稍后重试或联系客服。';
$_ADDONLANG['msg_enter_code'] = '请输入验证码。';
$_ADDONLANG['msg_invalid_code'] = '验证码错误，还可尝试 {left} 次。';
$_ADDONLANG['msg_code_expired'] = '验证码已过期或尚未获取，请重新获取。';
$_ADDONLANG['msg_too_many'] = '错误次数过多，请重新获取验证码。';
$_ADDONLANG['msg_verified_redirect'] = '邮箱验证成功，正在为您登录…';
$_ADDONLANG['msg_verified_login'] = '邮箱验证成功，请重新登录。';
$_ADDONLANG['msg_verified_inline'] = '邮箱已验证';
$_ADDONLANG['msg_invalid_email'] = '请先填写有效的邮箱地址。';
$_ADDONLANG['msg_email_registered'] = '该邮箱已注册，请直接登录。';
$_ADDONLANG['msg_session_expired'] = '验证会话已过期，请重新登录。';
$_ADDONLANG['msg_invalid_token'] = '页面已过期，请刷新后重试。';
$_ADDONLANG['msg_bad_request'] = '无效的请求。';
$_ADDONLANG['msg_network_error'] = '网络错误，请重试。';
$_ADDONLANG['msg_need_verify'] = '请先验证邮箱再继续。';
$_ADDONLANG['msg_login_blocked'] = '请先验证邮箱再登录。';
$_ADDONLANG['error_email_not_verified'] = '请先使用发送到邮箱的验证码完成邮箱验证。';

$_ADDONLANG['email_subject'] = '您的验证码：{$code}';
$_ADDONLANG['email_body'] = '<p>您好，</p>'
    . '<p>您的邮箱验证码为：</p>'
    . '<p style="font-size:26px;font-weight:bold;letter-spacing:6px;">{$code}</p>'
    . '<p>验证码将在 {$minutes} 分钟后失效。如果这不是您本人的操作，请忽略此邮件。</p>'
    . '<p>{$signature}</p>';

$_ADDONLANG['admin_stat_total'] = '用户总数';
$_ADDONLANG['admin_stat_verified'] = '已验证';
$_ADDONLANG['admin_stat_unverified'] = '未验证';
$_ADDONLANG['admin_search_placeholder'] = '按 ID、姓名或邮箱搜索';
$_ADDONLANG['admin_filter_all'] = '全部状态';
$_ADDONLANG['admin_filter_verified'] = '已验证';
$_ADDONLANG['admin_filter_unverified'] = '未验证';
$_ADDONLANG['admin_bulk_verify'] = '所选设为已验证';
$_ADDONLANG['admin_bulk_unverify'] = '所选设为未验证';
$_ADDONLANG['admin_verify_all'] = '将所有未验证用户设为已验证';
$_ADDONLANG['admin_col_id'] = 'ID';
$_ADDONLANG['admin_col_name'] = '姓名';
$_ADDONLANG['admin_col_email'] = '邮箱';
$_ADDONLANG['admin_col_clients'] = '客户账户';
$_ADDONLANG['admin_col_status'] = '状态';
$_ADDONLANG['admin_col_created'] = '创建时间';
$_ADDONLANG['admin_col_actions'] = '操作';
$_ADDONLANG['admin_loading'] = '加载中…';
$_ADDONLANG['admin_prev'] = '上一页';
$_ADDONLANG['admin_next'] = '下一页';
$_ADDONLANG['admin_status_verified'] = '已验证';
$_ADDONLANG['admin_status_unverified'] = '未验证';
$_ADDONLANG['admin_mark_verified'] = '设为已验证';
$_ADDONLANG['admin_mark_unverified'] = '设为未验证';
$_ADDONLANG['admin_no_results'] = '没有找到用户。';
$_ADDONLANG['admin_page_info'] = '第 {page} / {pages} 页，共 {total} 个用户';
$_ADDONLANG['admin_request_failed'] = '请求失败，请重试。';
$_ADDONLANG['admin_select_first'] = '请至少选择一个用户。';
$_ADDONLANG['admin_confirm_verify_all'] = '确定将所有未验证用户设为已验证吗？';
$_ADDONLANG['admin_confirm_unverify'] = '设为未验证后，用户下次访问时会被登出并需要重新验证邮箱。确定继续？';
$_ADDONLANG['admin_owner'] = '账户所有者';
$_ADDONLANG['admin_saved'] = '已更新 {count} 个用户。';
