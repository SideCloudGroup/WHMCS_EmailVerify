<?php

namespace EmailVerify;

final class CodeService
{
    const LOGIN = 'login';
    const REGISTER = 'register';
    const IP_WINDOW = 3600;

    public static function send($purpose, $email, $name)
    {
        $email = Session::normalizeEmail($email);
        $cooldownKey = 'cooldown.' . hash('sha256', $email);

        $left = Cache::remaining($cooldownKey);
        if ($left > 0) {
            return self::result(false, Lang::get('msg_cooldown', ['seconds' => $left]), ['cooldown' => $left]);
        }

        $limit = Config::int('ip_hourly_limit', 0, 10000);
        $ipKey = 'ip.' . hash('sha256', self::ip());
        $sentFromIp = (int) Cache::get($ipKey);
        if ($limit > 0 && $sentFromIp >= $limit) {
            return self::result(false, Lang::get('msg_rate_limited'));
        }

        $cooldown = Config::int('send_cooldown', 0, 3600);
        if ($cooldown > 0) {
            Cache::put($cooldownKey, 1, $cooldown);
        }
        if ($limit > 0) {
            Cache::put($ipKey, $sentFromIp + 1, Cache::remaining($ipKey) ?: self::IP_WINDOW);
        }

        $minutes = Config::int('code_ttl', 1, 1440);
        $code = self::generate(Config::codeLength());
        $codeKey = self::codeKey($purpose, $email);
        Cache::put($codeKey, ['hash' => self::hash($code, $codeKey), 'attempts' => 0], $minutes * 60);

        try {
            Mailer::sendCode($email, $name, $code, $minutes);
        } catch (\Throwable $e) {
            Cache::forget($codeKey);
            Cache::forget($cooldownKey);
            logActivity('Email Verify: failed to send verification code to ' . $email . ' - ' . $e->getMessage());
            return self::result(false, Lang::get('msg_send_failed'));
        }

        return self::result(true, Lang::get('msg_sent', ['email' => $email]), ['cooldown' => $cooldown]);
    }

    public static function verify($purpose, $email, $code)
    {
        $email = Session::normalizeEmail($email);
        $code = preg_replace('/\s+/', '', (string) $code);
        if ($code === '') {
            return self::result(false, Lang::get('msg_enter_code'));
        }

        $codeKey = self::codeKey($purpose, $email);
        $entry = Cache::get($codeKey);
        if (!is_array($entry) || empty($entry['hash'])) {
            return self::result(false, Lang::get('msg_code_expired'));
        }

        if (hash_equals($entry['hash'], self::hash($code, $codeKey))) {
            Cache::forget($codeKey);
            return self::result(true, '');
        }

        $max = Config::int('max_attempts', 1, 50);
        $entry['attempts'] = (int) $entry['attempts'] + 1;
        if ($entry['attempts'] >= $max) {
            Cache::forget($codeKey);
            return self::result(false, Lang::get('msg_too_many'));
        }
        Cache::replace($codeKey, $entry);

        return self::result(false, Lang::get('msg_invalid_code', ['left' => $max - $entry['attempts']]));
    }

    public static function cooldownLeft($email)
    {
        return Cache::remaining('cooldown.' . hash('sha256', Session::normalizeEmail($email)));
    }

    private static function codeKey($purpose, $email)
    {
        return 'code.' . hash('sha256', $purpose . '|' . $email . '|' . Session::nonce());
    }

    private static function hash($code, $salt)
    {
        return hash('sha256', $salt . '|' . $code);
    }

    private static function generate($length)
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }

    private static function ip()
    {
        if (is_callable(['\WHMCS\Utility\Environment\CurrentRequest', 'getIP'])) {
            $ip = \WHMCS\Utility\Environment\CurrentRequest::getIP();
            if ($ip) {
                return (string) $ip;
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    private static function result($ok, $message, array $extra = [])
    {
        return ['ok' => (bool) $ok, 'message' => $message] + $extra;
    }
}
