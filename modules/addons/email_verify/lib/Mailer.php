<?php

namespace EmailVerify;

use WHMCS\Config\Setting;
use WHMCS\Database\Capsule;
use WHMCS\Input\Sanitize;

final class Mailer
{
    const TEMPLATE = 'Email Verification Code';

    public static function sendCode($email, $name, $code, $minutes)
    {
        list($subject, $body) = self::content();
        $vars = self::mergeVars() + [
            'code' => $code,
            'minutes' => $minutes,
            'email' => $email,
            'client_name' => $name,
        ];

        $message = new \WHMCS\Mail\Message();
        $message->addRecipient('to', $email, $name !== '' ? $name : $email);
        $message->setFromEmail(Setting::getValue('SystemEmailsFromEmail') ?: Setting::getValue('Email'));
        $message->setFromName(Setting::getValue('SystemEmailsFromName') ?: Setting::getValue('CompanyName'));
        $message->setSubject(html_entity_decode(self::render($subject, $vars), ENT_QUOTES, 'UTF-8'));
        $message->setBodyFromSmarty(self::html($body, $vars));

        \WHMCS\Module\Mail::factory()->send($message);
    }

    /**
     * Create the editable email template (default + translations) if it does not exist yet.
     */
    public static function installTemplates()
    {
        if (Capsule::table('tblemailtemplates')->where('name', self::TEMPLATE)->exists()) {
            return;
        }
        $default = strtolower((string) Setting::getValue('Language')) ?: 'english';
        $variants = ['' => Lang::load($default)];
        foreach (['english', 'chinese'] as $language) {
            if ($language !== $default) {
                $variants[$language] = Lang::load($language);
            }
        }
        foreach ($variants as $language => $lines) {
            $template = new \WHMCS\Mail\Template();
            $template->type = 'general';
            $template->name = self::TEMPLATE;
            $template->subject = $lines['email_subject'];
            $template->message = $lines['email_body'];
            $template->language = $language;
            $template->custom = true;
            $template->disabled = false;
            $template->plaintext = false;
            $template->save();
        }
    }

    private static function content()
    {
        $language = Lang::current();
        $subject = '';
        $body = '';
        try {
            $rows = Capsule::table('tblemailtemplates')
                ->where('name', self::TEMPLATE)
                ->whereIn('language', [$language, ''])
                ->get(['language', 'subject', 'message']);
            foreach ($rows as $row) {
                if (trim(strip_tags($row->message)) === '') {
                    continue;
                }
                if ($row->language === $language || $body === '') {
                    $subject = $row->subject;
                    $body = $row->message;
                }
            }
        } catch (\Throwable $e) {
            $body = '';
        }
        if ($body === '') {
            $lines = Lang::all();
            $subject = $lines['email_subject'];
            $body = $lines['email_body'];
        }
        return [$subject, $body];
    }

    /**
     * The global CSS is inserted after rendering because its braces are not valid Smarty syntax.
     */
    private static function html($body, array $vars)
    {
        $header = Sanitize::decode((string) Setting::getValue('EmailGlobalHeader'));
        $footer = Sanitize::decode((string) Setting::getValue('EmailGlobalFooter'));
        $html = self::render($header, $vars) . self::render($body, $vars) . self::render($footer, $vars);
        return str_replace('[EmailCSS]', (string) Setting::getValue('EmailCSS'), $html);
    }

    private static function mergeVars()
    {
        $url = Url::base();
        $vars = [
            'company_name' => Setting::getValue('CompanyName'),
            'companyname' => Setting::getValue('CompanyName'),
            'company_domain' => Setting::getValue('Domain'),
            'company_logo_url' => '',
            'whmcs_url' => $url,
            'whmcs_link' => '<a href="' . Http::e($url) . '">' . Http::e($url) . '</a>',
            'signature' => nl2br(Sanitize::decode((string) Setting::getValue('Signature'))),
            'date' => date('Y-m-d'),
            'time' => date('H:i'),
            'charset' => Setting::getValue('Charset'),
        ];
        $app = isset($GLOBALS['whmcs']) ? $GLOBALS['whmcs'] : null;
        if (is_object($app) && method_exists($app, 'getLogoUrlForEmailTemplate')) {
            $vars['company_logo_url'] = $app->getLogoUrlForEmailTemplate();
        }
        return $vars;
    }

    private static function render($template, array $vars)
    {
        if (trim($template) === '') {
            return $template;
        }
        try {
            $smarty = new \WHMCS\Smarty(false, 'mail');
            $smarty->compile_id = md5($template);
            foreach ($vars as $key => $value) {
                $smarty->assign($key, $value);
            }
            $output = $smarty->fetch('string:' . $template);
            // WHMCS logs Smarty syntax errors and returns an empty string instead of throwing.
            if (is_string($output) && trim($output) !== '') {
                return $output;
            }
        } catch (\Throwable $e) {
            // Fall through to plain placeholder replacement.
        }
        $map = [];
        foreach ($vars as $key => $value) {
            if (is_scalar($value)) {
                $map['{$' . $key . '}'] = (string) $value;
            }
        }
        return strtr($template, $map);
    }
}
