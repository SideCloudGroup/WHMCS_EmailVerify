<?php

namespace EmailVerify;

use WHMCS\Database\Capsule;

final class Config
{
    const MODULE = 'email_verify';
    const VERSION = '1.0.1';

    private static $values;

    private static $defaults = [
        'block_login' => 'on',
        'require_on_register' => 'on',
        'require_captcha' => 'on',
        'suppress_native_mail' => 'on',
        'auto_login' => 'on',
        'code_length' => '6',
        'code_ttl' => '10',
        'send_cooldown' => '60',
        'max_attempts' => '5',
        'ip_hourly_limit' => '10',
        'support_label' => '',
        'support_url' => '',
    ];

    public static function get($key)
    {
        $values = self::all();
        if (array_key_exists($key, $values)) {
            return (string) $values[$key];
        }
        return isset(self::$defaults[$key]) ? self::$defaults[$key] : '';
    }

    public static function bool($key)
    {
        return in_array(strtolower(self::get($key)), ['on', 'yes', '1', 'true'], true);
    }

    public static function int($key, $min, $max)
    {
        $value = self::get($key);
        if (!is_numeric($value)) {
            $value = isset(self::$defaults[$key]) ? self::$defaults[$key] : $min;
        }
        return max($min, min($max, (int) $value));
    }

    public static function codeLength()
    {
        return self::int('code_length', 4, 8);
    }

    private static function all()
    {
        if (self::$values === null) {
            self::$values = [];
            try {
                $rows = Capsule::table('tbladdonmodules')->where('module', self::MODULE)->get(['setting', 'value']);
                foreach ($rows as $row) {
                    self::$values[$row->setting] = $row->value;
                }
            } catch (\Throwable $e) {
                // Fall back to defaults when the settings table is unavailable.
            }
        }
        return self::$values;
    }
}
