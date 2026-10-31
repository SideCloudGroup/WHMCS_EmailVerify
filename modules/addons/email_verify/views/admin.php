<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use EmailVerify\Http;
use EmailVerify\Url;

/** @var array $l */
/** @var array $config */
$t = function ($key) use ($l) {
    return Http::e(isset($l[$key]) ? $l[$key] : $key);
};
?>
<style>
    #ev-admin .ev-stats .label { display: inline-block; font-size: 12px; padding: 6px 10px; margin-right: 6px; }
    #ev-admin .ev-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 15px 0; }
    #ev-admin .ev-toolbar .form-control { width: auto; }
    #ev-admin .ev-toolbar [data-ev-search] { width: 280px; }
    #ev-admin .ev-spacer { flex: 1 1 auto; }
    #ev-admin table td { vertical-align: middle; }
    #ev-admin tbody.is-loading { opacity: .5; }
    #ev-admin .ev-pager { display: flex; justify-content: space-between; align-items: center; }
</style>
<div id="ev-admin">
    <div class="ev-stats">
        <span class="label label-default"><?= $t('admin_stat_total') ?>: <b data-ev-stat="total">-</b></span>
        <span class="label label-success"><?= $t('admin_stat_verified') ?>: <b data-ev-stat="verified">-</b></span>
        <span class="label label-danger"><?= $t('admin_stat_unverified') ?>: <b data-ev-stat="unverified">-</b></span>
    </div>

    <div class="ev-toolbar">
        <input type="search" class="form-control input-sm" data-ev-search placeholder="<?= $t('admin_search_placeholder') ?>">
        <select class="form-control input-sm" data-ev-status>
            <option value="all"><?= $t('admin_filter_all') ?></option>
            <option value="verified"><?= $t('admin_filter_verified') ?></option>
            <option value="unverified"><?= $t('admin_filter_unverified') ?></option>
        </select>
        <button type="button" class="btn btn-success btn-sm" data-ev-bulk="1" disabled><?= $t('admin_bulk_verify') ?></button>
        <button type="button" class="btn btn-default btn-sm" data-ev-bulk="0" disabled><?= $t('admin_bulk_unverify') ?></button>
        <span class="ev-spacer"></span>
        <button type="button" class="btn btn-warning btn-sm" data-ev-verify-all><?= $t('admin_verify_all') ?></button>
    </div>

    <div data-ev-alert></div>

    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th style="width:30px"><input type="checkbox" data-ev-check-all></th>
            <th><?= $t('admin_col_id') ?></th>
            <th><?= $t('admin_col_name') ?></th>
            <th><?= $t('admin_col_email') ?></th>
            <th><?= $t('admin_col_clients') ?></th>
            <th><?= $t('admin_col_status') ?></th>
            <th><?= $t('admin_col_created') ?></th>
            <th class="text-right"><?= $t('admin_col_actions') ?></th>
        </tr>
        </thead>
        <tbody data-ev-rows>
        <tr><td colspan="8" class="text-center text-muted"><?= $t('admin_loading') ?></td></tr>
        </tbody>
    </table>

    <div class="ev-pager">
        <span class="text-muted" data-ev-page-info></span>
        <div class="btn-group">
            <button type="button" class="btn btn-default btn-sm" data-ev-prev><?= $t('admin_prev') ?></button>
            <button type="button" class="btn btn-default btn-sm" data-ev-next><?= $t('admin_next') ?></button>
        </div>
    </div>
</div>
<script>window.EmailVerifyAdmin = <?= Http::scriptJson($config) ?>;</script>
<script src="<?= Http::e(Url::asset('js/admin.js')) ?>"></script>
