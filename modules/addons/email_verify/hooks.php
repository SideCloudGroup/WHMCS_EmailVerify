<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/bootstrap.php';

use EmailVerify\ClientController;
use EmailVerify\Config;
use EmailVerify\Gate;
use EmailVerify\Session;
use EmailVerify\Url;

add_hook('UserLogin', 1, function ($vars) {
    if (defined('ADMINAREA') || empty($vars['user']) || Gate::isMasquerading()) {
        return;
    }
    try {
        $userId = (int) $vars['user']->id;
        if (Gate::shouldBlock($userId)) {
            Gate::block($userId, Gate::returnTarget());
        }
    } catch (\Throwable $e) {
        logActivity('Email Verify: login check failed - ' . $e->getMessage());
    }
});

add_hook('ClientAreaPage', 1, function ($vars) {
    if ((isset($_GET['m']) ? $_GET['m'] : '') === Config::MODULE || Gate::isMasquerading()) {
        return;
    }
    try {
        $user = Gate::currentUser();
        if ($user) {
            if (Gate::shouldBlock((int) $user->id)) {
                Gate::block((int) $user->id, Gate::returnTarget());
            }
            return;
        }
        // A login request that swallowed our redirect (e.g. AJAX) still lands on the verification page next.
        $pending = Session::pending();
        if ($pending && empty($pending['visited'])) {
            header('Location: ' . Url::module(), true, 302);
            exit;
        }
    } catch (\Throwable $e) {
        logActivity('Email Verify: page check failed - ' . $e->getMessage());
    }
});

add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    if (!Config::bool('require_on_register')) {
        return '';
    }
    $filename = isset($vars['filename']) ? $vars['filename'] : '';
    $template = isset($vars['templatefile']) ? $vars['templatefile'] : '';
    if (!in_array($filename, ['register', 'cart'], true) && $template !== 'clientregister') {
        return '';
    }
    try {
        return Gate::currentUser() ? '' : ClientController::inlineAssets();
    } catch (\Throwable $e) {
        logActivity('Email Verify: unable to render the verification widget - ' . $e->getMessage());
        return '';
    }
});

add_hook('ClientDetailsValidation', 1, function ($vars) {
    $email = isset($vars['email']) ? $vars['email'] : (isset($_POST['email']) ? $_POST['email'] : '');
    return Gate::registrationErrors($email);
});

add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
    $custtype = isset($vars['custtype']) ? $vars['custtype'] : (isset($_POST['custtype']) ? $_POST['custtype'] : 'new');
    if ($custtype === 'existing') {
        return [];
    }
    $email = isset($vars['email']) ? $vars['email'] : (isset($_POST['email']) ? $_POST['email'] : '');
    return Gate::registrationErrors($email);
});

add_hook('UserAdd', 1, function ($vars) {
    $userId = isset($vars['user_id']) ? (int) $vars['user_id'] : (isset($vars['userid']) ? (int) $vars['userid'] : 0);
    Gate::adoptVerifiedEmail($userId, isset($vars['email']) ? $vars['email'] : '');
});

add_hook('ClientAdd', 1, function ($vars) {
    $userId = isset($vars['user_id']) ? (int) $vars['user_id'] : 0;
    Gate::adoptVerifiedEmail($userId, isset($vars['email']) ? $vars['email'] : '');
});

add_hook('EmailPreSend', 1, function ($vars) {
    $native = ['Email Address Verification', 'Client Email Address Verification'];
    if (Config::bool('block_login')
        && Config::bool('suppress_native_mail')
        && in_array(isset($vars['messagename']) ? $vars['messagename'] : '', $native, true)
    ) {
        return ['abortsend' => true];
    }
});
