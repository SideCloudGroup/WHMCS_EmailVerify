<?php

namespace EmailVerify;

use WHMCS\Database\Capsule;

final class AdminController
{
    const PER_PAGE = 25;
    const MAX_BULK = 500;

    public static function handle(array $vars)
    {
        Lang::useAdmin();
        $action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';

        if ($action === 'list') {
            self::listUsers();
        }
        if ($action === 'set' || $action === 'set_all') {
            if (!Http::isPost() || !Http::validToken()) {
                Http::fail(Lang::get('msg_invalid_token'), 403);
            }
            $action === 'set' ? self::setStatus() : self::verifyAll();
        }

        echo self::page($vars['modulelink']);
    }

    private static function listUsers()
    {
        $term = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $status = isset($_GET['status']) ? (string) $_GET['status'] : 'all';
        $page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);

        $query = Users::search($term, $status);
        $total = (clone $query)->count();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        $users = $query->orderBy('id', 'desc')
            ->forPage($page, self::PER_PAGE)
            ->get(['id', 'first_name', 'last_name', 'email', 'email_verified_at', 'created_at']);

        $clients = [];
        $ids = [];
        foreach ($users as $user) {
            $ids[] = (int) $user->id;
        }
        if ($ids) {
            $links = Capsule::table('tblusers_clients')
                ->whereIn('auth_user_id', $ids)
                ->orderBy('owner', 'desc')
                ->get(['auth_user_id', 'client_id', 'owner']);
            foreach ($links as $link) {
                $clients[(int) $link->auth_user_id][] = ['id' => (int) $link->client_id, 'owner' => (bool) $link->owner];
            }
        }

        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                'id' => (int) $user->id,
                'name' => Users::displayName($user),
                'email' => $user->email,
                'verified_at' => Users::isVerified($user) ? (string) $user->email_verified_at : null,
                'created_at' => (string) $user->created_at,
                'clients' => isset($clients[(int) $user->id]) ? $clients[(int) $user->id] : [],
            ];
        }

        $all = Capsule::table('tblusers')->count();
        $verified = Capsule::table('tblusers')->whereNotNull('email_verified_at')->count();

        Http::json([
            'ok' => true,
            'rows' => $rows,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'stats' => ['total' => $all, 'verified' => $verified, 'unverified' => $all - $verified],
        ]);
    }

    private static function setStatus()
    {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_slice($_POST['ids'], 0, self::MAX_BULK) : [];
        $verified = !empty($_POST['verified']);
        $count = Users::setVerified($ids, $verified);
        if ($count) {
            logActivity('Email Verify: admin marked user(s) #' . implode(', #', array_map('intval', $ids))
                . ' as ' . ($verified ? 'verified' : 'unverified'));
        }
        Http::json(['ok' => true, 'message' => Lang::get('admin_saved', ['count' => $count])]);
    }

    private static function verifyAll()
    {
        $count = Users::verifyAll();
        if ($count) {
            logActivity('Email Verify: admin marked all ' . $count . ' unverified user(s) as verified');
        }
        Http::json(['ok' => true, 'message' => Lang::get('admin_saved', ['count' => $count])]);
    }

    private static function page($modulelink)
    {
        $l = Lang::all();
        $config = [
            'endpoint' => $modulelink,
            'token' => generate_token('plain'),
            'lang' => Lang::only([
                'admin_status_verified', 'admin_status_unverified', 'admin_mark_verified', 'admin_mark_unverified',
                'admin_no_results', 'admin_page_info', 'admin_request_failed', 'admin_select_first',
                'admin_confirm_verify_all', 'admin_confirm_unverify', 'admin_owner',
            ]),
        ];
        ob_start();
        include dirname(__DIR__) . '/views/admin.php';
        return ob_get_clean();
    }
}
