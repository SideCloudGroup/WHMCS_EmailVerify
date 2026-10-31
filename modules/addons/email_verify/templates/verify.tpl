<link rel="stylesheet" href="{$ev.css}">

<div class="ev-card card panel panel-default">
    <div class="card-body panel-body">
        {if $ev.state eq 'pending'}
            <h3 class="ev-title">{$ev.lang.page_heading}</h3>
            <p class="text-muted">{$ev.intro nofilter}</p>

            <div data-ev-page>
                <div class="form-group">
                    <input type="text" class="form-control ev-code-input" data-ev-code
                           inputmode="numeric" autocomplete="one-time-code" maxlength="{$ev.codeLength}"
                           placeholder="{$ev.placeholder}">
                </div>
                <div class="ev-actions">
                    <button type="button" class="btn btn-default btn-outline-secondary" data-ev-send>{$ev.lang.btn_send}</button>
                    <button type="button" class="btn btn-primary" data-ev-verify>{$ev.lang.btn_verify}</button>
                </div>
                <div class="ev-msg" data-ev-msg role="status" aria-live="polite"></div>
            </div>

            <p class="ev-footer"><a href="{$ev.loginUrl}">{$ev.lang.use_other_account}</a></p>
        {else}
            <h3 class="ev-title">{$ev.lang.expired_heading}</h3>
            <p class="text-muted">{$ev.lang.expired_text}</p>
            <a href="{$ev.loginUrl}" class="btn btn-primary btn-block">{$ev.lang.btn_login}</a>
        {/if}

        {if $ev.support}
            <p class="ev-support text-muted">{$ev.support nofilter}</p>
        {/if}
    </div>
</div>

{if $ev.state eq 'pending'}
    <script>window.EmailVerifyConfig = {$ev.config nofilter};</script>
    <script src="{$ev.js}"></script>
{/if}
