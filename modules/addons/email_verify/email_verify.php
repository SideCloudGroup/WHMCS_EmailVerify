<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/bootstrap.php';

use EmailVerify\AdminController;
use EmailVerify\ClientController;
use EmailVerify\Config;
use EmailVerify\Lang;
use EmailVerify\Mailer;

function email_verify_config()
{
    $l = Lang::admin();
    $yesno = function ($key) use ($l) {
        return [
            'FriendlyName' => $l['cfg_' . $key],
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => $l['cfg_' . $key . '_desc'],
        ];
    };
    $number = function ($key, $default) use ($l) {
        return [
            'FriendlyName' => $l['cfg_' . $key],
            'Type' => 'text',
            'Size' => '6',
            'Default' => $default,
            'Description' => $l['cfg_' . $key . '_desc'],
        ];
    };

    return [
        'name' => 'Email Verify',
        'description' => $l['module_desc'],
        'author' => 'SideCloud',
        'language' => 'english',
        'version' => Config::VERSION,
        'fields' => [
            'block_login' => $yesno('block_login'),
            'require_on_register' => $yesno('require_on_register'),
            'require_captcha' => $yesno('require_captcha'),
            'suppress_native_mail' => $yesno('suppress_native_mail'),
            'auto_login' => $yesno('auto_login'),
            'code_length' => [
                'FriendlyName' => $l['cfg_code_length'],
                'Type' => 'dropdown',
                'Options' => '4,5,6,7,8',
                'Default' => '6',
                'Description' => $l['cfg_code_length_desc'],
            ],
            'code_ttl' => $number('code_ttl', '10'),
            'send_cooldown' => $number('send_cooldown', '60'),
            'max_attempts' => $number('max_attempts', '5'),
            'ip_hourly_limit' => $number('ip_hourly_limit', '10'),
            'support_label' => [
                'FriendlyName' => $l['cfg_support_label'],
                'Type' => 'text',
                'Size' => '30',
                'Default' => '',
                'Description' => $l['cfg_support_label_desc'],
            ],
            'support_url' => [
                'FriendlyName' => $l['cfg_support_url'],
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Description' => $l['cfg_support_url_desc'],
            ],
        ],
    ];
}

function email_verify_activate()
{
    $l = Lang::admin();
    try {
        Mailer::installTemplates();
    } catch (\Throwable $e) {
        logActivity('Email Verify: unable to create the email template, built-in content will be used - ' . $e->getMessage());
    }
    return ['status' => 'success', 'description' => $l['activated']];
}

function email_verify_deactivate()
{
    return ['status' => 'success', 'description' => Lang::admin()['deactivated']];
}

function email_verify_output($vars)
{
    AdminController::handle($vars);
}

function email_verify_clientarea($vars)
{
    return ClientController::handle($vars);
}
