<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

spl_autoload_register(function ($class) {
    $prefix = 'EmailVerify\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__ . '/lib/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
