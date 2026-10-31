<?php

namespace EmailVerify;

final class Session
{
    const KEY = 'emailverify';
    const PENDING_TTL = 1800;
    const VERIFIED_TTL = 7200;

    public static function normalizeEmail($email)
    {
        return strtolower(trim((string) $email));
    }

    /**
     * Random per-session value that survives session id regeneration.
     */
    public static function nonce()
    {
        $bag = &self::bag();
        if (empty($bag['nonce'])) {
            $bag['nonce'] = bin2hex(random_bytes(16));
        }
        return $bag['nonce'];
    }

    public static function setPending($userId, $returnTo)
    {
        $bag = &self::bag();
        $bag['pending'] = [
            'uid' => (int) $userId,
            'return' => $returnTo,
            'exp' => time() + self::PENDING_TTL,
            'visited' => false,
            'auto_sent' => false,
        ];
    }

    public static function pending()
    {
        $bag = &self::bag();
        if (empty($bag['pending']) || !is_array($bag['pending']) || $bag['pending']['exp'] < time()) {
            unset($bag['pending']);
            return null;
        }
        return $bag['pending'];
    }

    public static function updatePending(array $values)
    {
        $bag = &self::bag();
        if (!empty($bag['pending']) && is_array($bag['pending'])) {
            $bag['pending'] = array_merge($bag['pending'], $values);
        }
    }

    public static function clearPending()
    {
        $bag = &self::bag();
        unset($bag['pending']);
    }

    public static function markEmailVerified($email)
    {
        $bag = &self::bag();
        $bag['verified'][self::normalizeEmail($email)] = time() + self::VERIFIED_TTL;
    }

    public static function isEmailVerified($email)
    {
        return in_array(self::normalizeEmail($email), self::verifiedEmails(), true);
    }

    public static function verifiedEmails()
    {
        $bag = &self::bag();
        if (empty($bag['verified']) || !is_array($bag['verified'])) {
            return [];
        }
        $now = time();
        foreach ($bag['verified'] as $email => $expires) {
            if ($expires < $now) {
                unset($bag['verified'][$email]);
            }
        }
        return array_map('strval', array_keys($bag['verified']));
    }

    private static function &bag()
    {
        if (!isset($_SESSION[self::KEY]) || !is_array($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = [];
        }
        return $_SESSION[self::KEY];
    }
}
