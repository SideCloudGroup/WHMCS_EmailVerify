<?php

namespace EmailVerify;

use WHMCS\Config\Setting;
use WHMCS\Database\Capsule;

final class Lang
{
    /** Used when the language cannot be detected or has no translation file. */
    const FALLBACK = 'chinese';

    private static $files = [];
    private static $language;
    private static $lines;

    public static function load($language)
    {
        $language = self::sanitize($language);
        if (!isset(self::$files[$language])) {
            $translation = is_file(self::path($language)) ? $language : self::FALLBACK;
            $lines = self::read('english');
            if ($translation !== 'english') {
                $lines = array_merge($lines, self::read($translation));
            }
            self::$files[$language] = $lines;
        }
        return self::$files[$language];
    }

    public static function useAdmin()
    {
        self::$language = self::adminLanguage();
        self::$lines = self::load(self::$language);
    }

    public static function admin()
    {
        return self::load(self::adminLanguage());
    }

    public static function current()
    {
        if (self::$language === null) {
            self::$language = self::clientLanguage();
        }
        return self::$language;
    }

    public static function all()
    {
        if (self::$lines === null) {
            self::$lines = self::load(self::current());
        }
        return self::$lines;
    }

    public static function get($key, array $replace = [])
    {
        $lines = self::all();
        $text = isset($lines[$key]) ? $lines[$key] : $key;
        if ($replace) {
            $map = [];
            foreach ($replace as $name => $value) {
                $map['{' . $name . '}'] = $value;
            }
            $text = strtr($text, $map);
        }
        return $text;
    }

    public static function only(array $keys)
    {
        $lines = self::all();
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = isset($lines[$key]) ? $lines[$key] : $key;
        }
        return $result;
    }

    private static function path($language)
    {
        return dirname(__DIR__) . '/lang/' . $language . '.php';
    }

    private static function read($language)
    {
        $file = self::path($language);
        if (!is_file($file)) {
            return [];
        }
        $_ADDONLANG = [];
        include $file;
        return is_array($_ADDONLANG) ? $_ADDONLANG : [];
    }

    private static function sanitize($language)
    {
        $language = strtolower(trim((string) $language));
        return preg_match('/^[a-z0-9_\-]+$/', $language) ? $language : self::FALLBACK;
    }

    private static function clientLanguage()
    {
        $language = '';
        try {
            if (class_exists('\Lang') && is_callable(['\Lang', 'getName'])) {
                $language = (string) \Lang::getName();
            }
        } catch (\Throwable $e) {
            $language = '';
        }
        if (!$language && isset($GLOBALS['_LANG']['locale']) && stripos($GLOBALS['_LANG']['locale'], 'zh') === 0) {
            $language = 'chinese';
        }
        if (!$language && isset($_SESSION['Language'])) {
            $language = $_SESSION['Language'];
        }
        try {
            if (!$language && !empty($_SESSION['uid'])) {
                $language = Capsule::table('tblclients')->where('id', (int) $_SESSION['uid'])->value('language');
            }
            if (!$language) {
                $language = Setting::getValue('Language');
            }
        } catch (\Throwable $e) {
            $language = '';
        }
        return self::sanitize($language ?: self::FALLBACK);
    }

    private static function adminLanguage()
    {
        $language = '';
        try {
            if (!empty($_SESSION['adminid'])) {
                $language = Capsule::table('tbladmins')->where('id', (int) $_SESSION['adminid'])->value('language');
            }
        } catch (\Throwable $e) {
            $language = '';
        }
        return self::sanitize($language ?: self::FALLBACK);
    }
}
