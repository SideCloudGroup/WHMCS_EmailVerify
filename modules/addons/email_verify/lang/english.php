<?php

$_ADDONLANG['module_desc'] = 'Requires users to verify their email address with a one-time code before they can log in, register or place an order.';
$_ADDONLANG['activated'] = 'Email Verify has been activated. Review the settings below before enabling it for your clients.';
$_ADDONLANG['deactivated'] = 'Email Verify has been deactivated. Verification status stored in WHMCS is kept.';

$_ADDONLANG['cfg_block_login'] = 'Block unverified login';
$_ADDONLANG['cfg_block_login_desc'] = 'Users with an unverified email address are logged out and must enter a code sent to their inbox before they can log in.';
$_ADDONLANG['cfg_require_on_register'] = 'Verify before registration / checkout';
$_ADDONLANG['cfg_require_on_register_desc'] = 'New customers must verify their email address on the registration and checkout pages before the account is created.';
$_ADDONLANG['cfg_require_captcha'] = 'Captcha before sending code';
$_ADDONLANG['cfg_require_captcha_desc'] = 'Require a Cap captcha before a code is sent on the registration / checkout pages. Uses the whmcs-cap-captcha configuration and is skipped when that hook is not installed or configured.';
$_ADDONLANG['cfg_suppress_native_mail'] = 'Suppress built-in verification email';
$_ADDONLANG['cfg_suppress_native_mail_desc'] = 'Stop WHMCS from sending its own link-based verification email while login blocking is enabled.';
$_ADDONLANG['cfg_auto_login'] = 'Log in after verification';
$_ADDONLANG['cfg_auto_login_desc'] = 'Log the user in automatically after verifying (uses the CreateSsoToken API). Otherwise the user is sent back to the login page.';
$_ADDONLANG['cfg_code_length'] = 'Code length';
$_ADDONLANG['cfg_code_length_desc'] = 'Number of digits in the verification code.';
$_ADDONLANG['cfg_code_ttl'] = 'Code lifetime';
$_ADDONLANG['cfg_code_ttl_desc'] = 'Minutes a code stays valid.';
$_ADDONLANG['cfg_send_cooldown'] = 'Resend cooldown';
$_ADDONLANG['cfg_send_cooldown_desc'] = 'Seconds before another code can be sent to the same email address.';
$_ADDONLANG['cfg_max_attempts'] = 'Max attempts';
$_ADDONLANG['cfg_max_attempts_desc'] = 'Wrong entries allowed per code before it is invalidated.';
$_ADDONLANG['cfg_ip_hourly_limit'] = 'Hourly limit per IP';
$_ADDONLANG['cfg_ip_hourly_limit_desc'] = 'Maximum codes sent per IP address per hour. 0 means unlimited.';

$_ADDONLANG['cfg_support_label'] = 'Support contact';
$_ADDONLANG['cfg_support_label_desc'] = 'Shown on the verification page for users who cannot complete verification. Leave empty to hide.';
$_ADDONLANG['cfg_support_url'] = 'Support link';
$_ADDONLANG['cfg_support_url_desc'] = 'Optional. http(s)://, mailto: or tg:// link for the support contact.';

$_ADDONLANG['page_title'] = 'Email Verification';
$_ADDONLANG['page_heading'] = 'Verify your email address';
$_ADDONLANG['page_intro'] = 'Before you can log in, please verify {email}. Enter the code we sent to this address.';
$_ADDONLANG['expired_heading'] = 'Verification session expired';
$_ADDONLANG['expired_text'] = 'Please log in again to continue verifying your email address.';
$_ADDONLANG['btn_login'] = 'Log in';
$_ADDONLANG['use_other_account'] = 'Log in with a different account';
$_ADDONLANG['support_text'] = 'If you run into any problems completing verification, please contact {contact}.';
$_ADDONLANG['code_placeholder'] = 'Enter the {length}-digit code';
$_ADDONLANG['btn_send'] = 'Send code';
$_ADDONLANG['btn_sending'] = 'Sending...';
$_ADDONLANG['btn_captcha'] = 'Checking...';
$_ADDONLANG['msg_captcha_failed'] = 'Captcha verification failed. Please try again.';
$_ADDONLANG['btn_resend_in'] = 'Resend in {seconds}s';
$_ADDONLANG['btn_verify'] = 'Verify';
$_ADDONLANG['btn_verifying'] = 'Verifying...';

$_ADDONLANG['msg_sent'] = 'A verification code has been sent to {email}.';
$_ADDONLANG['msg_cooldown'] = 'Please wait {seconds} seconds before requesting another code.';
$_ADDONLANG['msg_rate_limited'] = 'Too many codes have been requested from your network. Please try again later.';
$_ADDONLANG['msg_send_failed'] = 'The verification email could not be sent. Please try again later or contact support.';
$_ADDONLANG['msg_enter_code'] = 'Please enter the verification code.';
$_ADDONLANG['msg_invalid_code'] = 'The code is incorrect. {left} attempt(s) remaining.';
$_ADDONLANG['msg_code_expired'] = 'The code has expired or was not requested. Please request a new one.';
$_ADDONLANG['msg_too_many'] = 'Too many incorrect attempts. Please request a new code.';
$_ADDONLANG['msg_verified_redirect'] = 'Your email address has been verified. Logging you in...';
$_ADDONLANG['msg_verified_login'] = 'Your email address has been verified. Please log in again.';
$_ADDONLANG['msg_verified_inline'] = 'Email address verified';
$_ADDONLANG['msg_invalid_email'] = 'Please enter a valid email address first.';
$_ADDONLANG['msg_email_registered'] = 'This email address is already registered. Please log in instead.';
$_ADDONLANG['msg_session_expired'] = 'Your verification session has expired. Please log in again.';
$_ADDONLANG['msg_invalid_token'] = 'Your session has expired. Please refresh the page and try again.';
$_ADDONLANG['msg_bad_request'] = 'Invalid request.';
$_ADDONLANG['msg_network_error'] = 'Network error. Please try again.';
$_ADDONLANG['msg_need_verify'] = 'Please verify your email address before continuing.';
$_ADDONLANG['msg_login_blocked'] = 'Please verify your email address before logging in.';
$_ADDONLANG['error_email_not_verified'] = 'Please verify your email address with the code sent to your inbox before continuing.';

$_ADDONLANG['email_subject'] = 'Your verification code: {$code}';
$_ADDONLANG['email_body'] = '<p>Hello,</p>'
    . '<p>Your email verification code is:</p>'
    . '<p style="font-size:26px;font-weight:bold;letter-spacing:6px;">{$code}</p>'
    . '<p>This code expires in {$minutes} minutes. If you did not request it, you can safely ignore this email.</p>'
    . '<p>{$signature}</p>';

$_ADDONLANG['admin_stat_total'] = 'Users';
$_ADDONLANG['admin_stat_verified'] = 'Verified';
$_ADDONLANG['admin_stat_unverified'] = 'Unverified';
$_ADDONLANG['admin_search_placeholder'] = 'Search by ID, name or email';
$_ADDONLANG['admin_filter_all'] = 'All statuses';
$_ADDONLANG['admin_filter_verified'] = 'Verified';
$_ADDONLANG['admin_filter_unverified'] = 'Unverified';
$_ADDONLANG['admin_bulk_verify'] = 'Mark selected verified';
$_ADDONLANG['admin_bulk_unverify'] = 'Mark selected unverified';
$_ADDONLANG['admin_verify_all'] = 'Mark all unverified users as verified';
$_ADDONLANG['admin_col_id'] = 'ID';
$_ADDONLANG['admin_col_name'] = 'Name';
$_ADDONLANG['admin_col_email'] = 'Email';
$_ADDONLANG['admin_col_clients'] = 'Client accounts';
$_ADDONLANG['admin_col_status'] = 'Status';
$_ADDONLANG['admin_col_created'] = 'Created';
$_ADDONLANG['admin_col_actions'] = 'Actions';
$_ADDONLANG['admin_loading'] = 'Loading...';
$_ADDONLANG['admin_prev'] = 'Previous';
$_ADDONLANG['admin_next'] = 'Next';
$_ADDONLANG['admin_status_verified'] = 'Verified';
$_ADDONLANG['admin_status_unverified'] = 'Unverified';
$_ADDONLANG['admin_mark_verified'] = 'Mark verified';
$_ADDONLANG['admin_mark_unverified'] = 'Mark unverified';
$_ADDONLANG['admin_no_results'] = 'No users found.';
$_ADDONLANG['admin_page_info'] = 'Page {page} of {pages} ({total} users)';
$_ADDONLANG['admin_request_failed'] = 'Request failed. Please try again.';
$_ADDONLANG['admin_select_first'] = 'Please select at least one user.';
$_ADDONLANG['admin_confirm_verify_all'] = 'Mark ALL unverified users as verified?';
$_ADDONLANG['admin_confirm_unverify'] = 'Unverified users will be logged out and must verify their email again at next login. Continue?';
$_ADDONLANG['admin_owner'] = 'Account owner';
$_ADDONLANG['admin_saved'] = '{count} user(s) updated.';
