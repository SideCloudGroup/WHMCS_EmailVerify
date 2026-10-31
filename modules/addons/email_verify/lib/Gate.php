<?php

namespace EmailVerify;

use WHMCS\Database\Capsule;

final class Gate
{
    const RETURN_TARGETS = ['clientarea.php', 'cart.php?a=checkout'];

    private static $registrationErrorReported = false;

    public static function currentUser()
    {
        try {
            $current = new \WHMCS\Authentication\CurrentUser();
            $user = $current->user();
            return $user ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function isMasquerading()
    {
        try {
            return (bool) (new \WHMCS\Authentication\CurrentUser())->isMasqueradingAdmin();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function shouldBlock($userId)
    {
        if (!Config::bool('block_login')) {
            return false;
        }
        $user = Users::find($userId);
        if (!$user || Users::isVerified($user)) {
            return false;
        }
        if (Session::isEmailVerified($user->email)) {
            self::markVerified($user, 'pre-registration code');
            return false;
        }
        return true;
    }

    /**
     * Log the user out, remember who they are for the verification page and optionally redirect there.
     */
    public static function block($userId, $returnTo, $redirect = true)
    {
        self::logout($userId);
        Session::setPending($userId, in_array($returnTo, self::RETURN_TARGETS, true) ? $returnTo : 'clientarea.php');
        if (!$redirect) {
            return;
        }
        $url = Url::module();
        if (Http::isAjax()) {
            $message = Lang::get('msg_login_blocked');
            Http::json(['ok' => false, 'result' => 'error', 'message' => $message, 'error' => $message, 'redirect' => $url]);
        }
        header('Location: ' . $url, true, 302);
        exit;
    }

    public static function returnTarget()
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
        return $script === 'cart.php' ? 'cart.php?a=checkout' : 'clientarea.php';
    }

    /**
     * @return array{url: string, sso: bool}
     */
    public static function loginRedirect($userId, $returnTo)
    {
        $returnTo = in_array($returnTo, self::RETURN_TARGETS, true) ? $returnTo : 'clientarea.php';
        if (Config::bool('auto_login')) {
            try {
                $result = localAPI('CreateSsoToken', [
                    'user_id' => (int) $userId,
                    'destination' => 'sso:custom_redirect',
                    'sso_redirect_path' => $returnTo,
                ]);
                if (isset($result['result']) && $result['result'] === 'success' && !empty($result['redirect_url'])) {
                    return ['url' => $result['redirect_url'], 'sso' => true];
                }
                logActivity('Email Verify: automatic login failed - ' . (isset($result['message']) ? $result['message'] : 'unknown error'));
            } catch (\Throwable $e) {
                logActivity('Email Verify: automatic login failed - ' . $e->getMessage());
            }
        }
        return ['url' => Url::path() . 'login.php', 'sso' => false];
    }

    /**
     * Errors for registration/checkout forms submitted with an unverified email address.
     */
    public static function registrationErrors($email)
    {
        if (self::$registrationErrorReported
            || defined('ADMINAREA')
            || !Config::bool('require_on_register')
            || trim((string) $email) === ''
            || self::currentUser()
            || Session::isEmailVerified($email)
        ) {
            return [];
        }
        self::$registrationErrorReported = true;
        return [Lang::get('error_email_not_verified')];
    }

    /**
     * Carry a session-verified email address over to a newly created user.
     */
    public static function adoptVerifiedEmail($userId, $email)
    {
        if (defined('ADMINAREA')) {
            return;
        }
        $user = $userId ? Users::find($userId) : Users::findByEmail($email);
        if ($user && !Users::isVerified($user) && Session::isEmailVerified($user->email)) {
            self::markVerified($user, 'pre-registration code');
        }
    }

    public static function markVerified($user, $via)
    {
        if (Users::setVerified([$user->id], true)) {
            logActivity('Email Verify: user #' . $user->id . ' (' . $user->email . ') verified email address via ' . $via);
        }
    }

    private static function logout($userId)
    {
        foreach (['login_auth_tk', 'uid', 'upw', 'cid'] as $key) {
            unset($_SESSION[$key]);
        }
        try {
            Capsule::table('tblusers')->where('id', (int) $userId)->update(['remember_token' => '']);
        } catch (\Throwable $e) {
            // Column missing on this WHMCS version; the remember-me cookie is handled below.
        }
        if (is_callable(['\WHMCS\Cookie', 'delete'])) {
            \WHMCS\Cookie::delete('User');
        }
    }
}
