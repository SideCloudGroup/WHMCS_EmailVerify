<?php

namespace EmailVerify;

final class Http
{
    public static function json(array $data, $status = 200)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function fail($message, $status = 200, array $extra = [])
    {
        self::json(['ok' => false, 'message' => $message] + $extra, $status);
    }

    public static function isPost()
    {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }

    public static function isAjax()
    {
        $requestedWith = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) : '';
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
        return $requestedWith === 'xmlhttprequest' || strpos($accept, 'application/json') !== false;
    }

    public static function validToken()
    {
        $token = isset($_POST['token']) ? $_POST['token'] : '';
        return is_string($token) && $token !== '' && hash_equals((string) generate_token('plain'), $token);
    }

    public static function scriptJson($data)
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    }

    public static function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
