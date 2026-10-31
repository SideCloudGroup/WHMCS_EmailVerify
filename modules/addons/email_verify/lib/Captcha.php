<?php

namespace EmailVerify;

/**
 * Bridge to the whmcs-cap-captcha hook (includes/hooks/hybula_cap.php).
 * Reuses its endpoint and secret so the captcha is configured in one place.
 */
final class Captcha
{
    public static function enabled()
    {
        if (!Config::bool('require_captcha')
            || !function_exists('hybula_cap_verify')
            || !function_exists('hybula_cap_endpoint')
            || !empty($_SESSION['adminid'])
        ) {
            return false;
        }
        foreach (['hybulaCapUrl', 'hybulaCapSite', 'hybulaCapSecret'] as $name) {
            if (!defined($name) || constant($name) === '') {
                return false;
            }
        }
        return !defined('hybulaCapEnabled') || constant('hybulaCapEnabled');
    }

    public static function verify($token)
    {
        if (!is_string($token) || $token === '') {
            logActivity('Email Verify: captcha rejected - no cap_token was submitted with the send request');
            return false;
        }
        if (!hybula_cap_verify($token)) {
            logActivity('Email Verify: captcha rejected - siteverify did not accept the token (length ' . strlen($token) . ')');
            return false;
        }
        return true;
    }

    public static function clientConfig()
    {
        if (!self::enabled()) {
            return null;
        }
        return [
            'endpoint' => hybula_cap_endpoint(),
            'script' => defined('hybulaCapWidgetScript') ? constant('hybulaCapWidgetScript') : 'https://cdn.jsdelivr.net/npm/@cap.js/widget',
        ];
    }
}
