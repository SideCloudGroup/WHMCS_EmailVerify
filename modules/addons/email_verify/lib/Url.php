<?php

namespace EmailVerify;

use WHMCS\Config\Setting;

final class Url
{
    public static function base()
    {
        return rtrim((string) Setting::getValue('SystemURL'), '/') . '/';
    }

    /**
     * Host-relative root path of the WHMCS installation, e.g. "/" or "/billing/".
     */
    public static function path()
    {
        $path = parse_url(self::base(), PHP_URL_PATH);
        return rtrim($path ?: '', '/') . '/';
    }

    public static function module(array $query = [])
    {
        return self::path() . 'index.php?' . http_build_query(['m' => Config::MODULE] + $query);
    }

    public static function asset($file)
    {
        $mtime = @filemtime(dirname(__DIR__) . '/assets/' . $file);
        return self::path() . 'modules/addons/' . Config::MODULE . '/assets/' . $file . '?v=' . ($mtime ?: Config::VERSION);
    }
}
