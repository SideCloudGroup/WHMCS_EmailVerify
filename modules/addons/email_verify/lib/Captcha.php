<?php

namespace EmailVerify;

/**
 * Bridge to the whmcs-cap-captcha hook (includes/hooks/hybula_cap.php).
 * Uses hybula_captcha_driver() so send-code follows Turnstile, Cap or hCaptcha.
 */
final class Captcha
{
    /**
     * Whether register send-code must present a captcha token.
     * Admin sessions stay excluded; a missing driver must not skip verification.
     */
    public static function required()
    {
        return Config::bool('require_captcha') && empty($_SESSION['adminid']);
    }

    public static function verify($token)
    {
        if (!is_string($token) || $token === '') {
            logActivity('Email Verify: captcha rejected - no captcha token was submitted with the send request');
            return false;
        }
        $driver = self::driver();
        if ($driver === null) {
            logActivity('Email Verify: captcha rejected - hybula captcha driver is missing or not configured');
            return false;
        }
        try {
            if (!$driver->verify($token)) {
                logActivity('Email Verify: captcha rejected - siteverify did not accept the token (length ' . strlen($token) . ')');
                return false;
            }
        } catch (\Throwable $e) {
            logActivity('Email Verify: captcha rejected - ' . $e->getMessage());
            return false;
        }
        return true;
    }

    public static function clientConfig()
    {
        if (!self::required()) {
            return null;
        }
        $driver = self::driver();
        if ($driver === null) {
            return ['required' => true];
        }
        $widget = method_exists($driver, 'widgetHtml') ? (string) $driver->widgetHtml() : '';
        $endpoint = self::widgetAttribute($widget, 'cap-api-endpoint');
        if ($endpoint === '' && function_exists('hybula_captcha_cap_endpoint')) {
            $endpoint = (string) hybula_captcha_cap_endpoint();
        }
        $name = defined('hybulaCaptchaDriver') ? (string) constant('hybulaCaptchaDriver') : '';
        return [
            'required' => true,
            'driver' => $name,
            'field' => method_exists($driver, 'tokenField') ? (string) $driver->tokenField() : '',
            'script' => method_exists($driver, 'scriptSrc') ? (string) $driver->scriptSrc() : '',
            'scriptAsync' => method_exists($driver, 'scriptAsync') ? (bool) $driver->scriptAsync() : true,
            'widget' => $widget,
            'selector' => method_exists($driver, 'widgetSelector') ? (string) $driver->widgetSelector() : '',
            'sitekey' => self::widgetAttribute($widget, 'sitekey'),
            'theme' => self::widgetAttribute($widget, 'theme'),
            'endpoint' => $endpoint,
            // Send-code uses an on-click challenge, not the visible register-form widget.
            'execution' => 'execute',
            'appearance' => 'execute',
            'size' => $name === 'hcaptcha' ? 'invisible' : '',
        ];
    }

    private static function driver()
    {
        if (!function_exists('hybula_captcha_driver')) {
            return null;
        }
        $driver = hybula_captcha_driver();
        if (!is_object($driver) || !method_exists($driver, 'isConfigured') || !method_exists($driver, 'verify')) {
            return null;
        }
        try {
            if (!$driver->isConfigured()) {
                return null;
            }
        } catch (\Throwable $e) {
            return null;
        }
        return $driver;
    }

    private static function widgetAttribute($html, $name)
    {
        $pattern = '/\bdata-' . preg_quote($name, '/') . '=(["\'])(.*?)\1/i';
        if (!preg_match($pattern, (string) $html, $match)) {
            return '';
        }
        return html_entity_decode($match[2], ENT_QUOTES, 'UTF-8');
    }
}
