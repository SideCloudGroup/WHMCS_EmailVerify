<?php

namespace EmailVerify;

use WHMCS\TransientData;

/**
 * Short-lived key/value storage backed by WHMCS TransientData.
 * Every entry carries its own expiry so stale rows are never trusted.
 */
final class Cache
{
    const PREFIX = 'emailverify.';

    public static function get($key)
    {
        $entry = self::read($key);
        return $entry === null ? null : $entry['v'];
    }

    public static function remaining($key)
    {
        $entry = self::read($key);
        return $entry === null ? 0 : max(0, $entry['e'] - time());
    }

    public static function put($key, $value, $ttl)
    {
        $ttl = max(1, (int) $ttl);
        $store = TransientData::getInstance();
        $store->delete(self::PREFIX . $key);
        $store->store(self::PREFIX . $key, json_encode(['e' => time() + $ttl, 'v' => $value]), $ttl);
    }

    /**
     * Replace the value while keeping the original expiry.
     */
    public static function replace($key, $value)
    {
        $left = self::remaining($key);
        if ($left > 0) {
            self::put($key, $value, $left);
        }
    }

    public static function forget($key)
    {
        TransientData::getInstance()->delete(self::PREFIX . $key);
    }

    private static function read($key)
    {
        $raw = TransientData::getInstance()->retrieve(self::PREFIX . $key);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $entry = json_decode($raw, true);
        if (!is_array($entry) || !isset($entry['e']) || !array_key_exists('v', $entry) || $entry['e'] <= time()) {
            self::forget($key);
            return null;
        }
        return $entry;
    }
}
