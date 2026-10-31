<?php

namespace EmailVerify;

final class ClientController
{
    public static function handle(array $vars)
    {
        $action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
        if ($action !== '') {
            self::dispatch($action);
        }
        return self::page();
    }

    /**
     * Config consumed by assets/js/client.js.
     */
    public static function scriptConfig($mode, array $extra = [])
    {
        return [
            'mode' => $mode,
            'endpoint' => Url::module(),
            'token' => generate_token('plain'),
            'codeLength' => Config::codeLength(),
            'lang' => Lang::only([
                'code_placeholder', 'btn_send', 'btn_sending', 'btn_captcha', 'btn_resend_in', 'btn_verify', 'btn_verifying',
                'msg_captcha_failed',
                'msg_enter_code', 'msg_invalid_email', 'msg_network_error', 'msg_need_verify', 'msg_verified_inline',
            ]),
        ] + $extra;
    }

    public static function inlineAssets()
    {
        $config = self::scriptConfig(CodeService::REGISTER, [
            'verified' => Session::verifiedEmails(),
            'captcha' => Captcha::clientConfig(),
        ]);
        return '<link rel="stylesheet" href="' . Http::e(Url::asset('css/client.css')) . '">'
            . '<script>window.EmailVerifyConfig = ' . Http::scriptJson($config) . ';</script>'
            . '<script src="' . Http::e(Url::asset('js/client.js')) . '" defer></script>';
    }

    private static function dispatch($action)
    {
        if (!Http::isPost()) {
            Http::fail(Lang::get('msg_bad_request'), 405);
        }
        if (!Http::validToken()) {
            Http::fail(Lang::get('msg_invalid_token'), 403);
        }
        switch ($action) {
            case 'login_send':
                self::loginSend();
                break;
            case 'login_verify':
                self::loginVerify();
                break;
            case 'register_send':
                self::registerSend();
                break;
            case 'register_verify':
                self::registerVerify();
                break;
        }
        Http::fail(Lang::get('msg_bad_request'), 400);
    }

    private static function loginSend()
    {
        $user = self::pendingUser();
        if (Users::isVerified($user)) {
            self::finishLogin($user);
        }
        Http::json(CodeService::send(CodeService::LOGIN, $user->email, Users::displayName($user)));
    }

    private static function loginVerify()
    {
        $user = self::pendingUser();
        if (!Users::isVerified($user)) {
            $result = CodeService::verify(CodeService::LOGIN, $user->email, self::input('code'));
            if (!$result['ok']) {
                Http::json($result);
            }
            Gate::markVerified($user, 'login code');
        }
        self::finishLogin($user);
    }

    private static function registerSend()
    {
        $email = self::registrationEmail();
        if (Session::isEmailVerified($email)) {
            Http::json(['ok' => true, 'verified' => true, 'message' => Lang::get('msg_verified_inline')]);
        }
        if (Captcha::enabled() && !Captcha::verify(self::input('cap_token'))) {
            Http::fail(Lang::get('msg_captcha_failed'));
        }
        Http::json(CodeService::send(CodeService::REGISTER, $email, ''));
    }

    private static function registerVerify()
    {
        $email = self::registrationEmail();
        $result = CodeService::verify(CodeService::REGISTER, $email, self::input('code'));
        if ($result['ok']) {
            Session::markEmailVerified($email);
            $result['message'] = Lang::get('msg_verified_inline');
        }
        Http::json($result);
    }

    private static function registrationEmail()
    {
        if (Gate::currentUser()) {
            Http::fail(Lang::get('msg_bad_request'));
        }
        $email = Session::normalizeEmail(self::input('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Http::fail(Lang::get('msg_invalid_email'));
        }
        if (Users::findByEmail($email)) {
            Http::fail(Lang::get('msg_email_registered'));
        }
        return $email;
    }

    private static function pendingUser()
    {
        $pending = Session::pending();
        $user = $pending ? Users::find($pending['uid']) : null;
        if (!$user) {
            Session::clearPending();
            Http::fail(Lang::get('msg_session_expired'), 200, ['expired' => true]);
        }
        return $user;
    }

    private static function finishLogin($user)
    {
        $pending = Session::pending();
        Session::clearPending();
        $redirect = Gate::loginRedirect($user->id, $pending ? $pending['return'] : 'clientarea.php');
        Http::json([
            'ok' => true,
            'message' => Lang::get($redirect['sso'] ? 'msg_verified_redirect' : 'msg_verified_login'),
            'redirect' => $redirect['url'],
        ]);
    }

    private static function page()
    {
        $current = Gate::currentUser();
        if ($current && !Gate::isMasquerading()) {
            if (!Gate::shouldBlock($current->id)) {
                header('Location: ' . Url::path() . 'clientarea.php');
                exit;
            }
            Gate::block($current->id, 'clientarea.php', false);
        }

        $pending = Session::pending();
        $user = $pending ? Users::find($pending['uid']) : null;
        $lang = Lang::all();
        $ev = [
            'state' => $user ? 'pending' : 'expired',
            'lang' => $lang,
            'css' => Url::asset('css/client.css'),
            'js' => Url::asset('js/client.js'),
            'loginUrl' => Url::path() . 'login.php',
            'codeLength' => Config::codeLength(),
            'placeholder' => Lang::get('code_placeholder', ['length' => Config::codeLength()]),
            'support' => self::supportText(),
        ];

        if ($user) {
            $cooldown = CodeService::cooldownLeft($user->email);
            $ev['intro'] = Lang::get('page_intro', ['email' => '<strong>' . Http::e($user->email) . '</strong>']);
            $ev['config'] = Http::scriptJson(self::scriptConfig(CodeService::LOGIN, [
                'cooldown' => $cooldown,
                'autoSend' => empty($pending['auto_sent']) && $cooldown === 0,
            ]));
            Session::updatePending(['visited' => true, 'auto_sent' => true]);
        }

        return [
            'pagetitle' => $lang['page_title'],
            'breadcrumb' => ['index.php?m=' . Config::MODULE => $lang['page_title']],
            'templatefile' => 'verify',
            'requirelogin' => false,
            'vars' => ['ev' => $ev],
        ];
    }

    private static function supportText()
    {
        $label = trim(Config::get('support_label'));
        if ($label === '') {
            return '';
        }
        $contact = Http::e($label);
        $url = trim(Config::get('support_url'));
        if ($url !== '' && preg_match('#^(https?://|mailto:|tg://)#i', $url)) {
            $contact = '<a href="' . Http::e($url) . '" target="_blank" rel="noopener noreferrer">' . $contact . '</a>';
        }
        return Lang::get('support_text', ['contact' => $contact]);
    }

    private static function input($key)
    {
        return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
    }
}
