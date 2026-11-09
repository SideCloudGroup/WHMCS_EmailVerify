(function () {
    'use strict';

    var cfg = window.EmailVerifyConfig;
    if (!cfg || window.EmailVerifyReady) {
        return;
    }
    window.EmailVerifyReady = true;

    var t = cfg.lang || {};
    var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var INLINE_SELECTOR = [
        '#frmCheckout input[name="email"]',
        'form[action*="register"] input[name="email"]',
        'form[action*="a=checkout"] input[name="email"]'
    ].join(',');

    function format(text, vars) {
        return String(text || '').replace(/\{(\w+)\}/g, function (match, key) {
            return vars && vars[key] !== undefined ? vars[key] : match;
        });
    }

    function normalize(email) {
        return String(email || '').trim().toLowerCase();
    }

    function isVisible(el) {
        return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    }

    function request(action, data) {
        var body = new URLSearchParams();
        body.append('action', action);
        body.append('token', cfg.token);
        Object.keys(data || {}).forEach(function (key) {
            body.append(key, data[key]);
        });
        return fetch(cfg.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function (response) {
            return response.json();
        }).catch(function () {
            return { ok: false, message: t.msg_network_error };
        });
    }

    var captchaScriptLoading = null;

    function captchaDriver() {
        var spec = cfg.captcha || {};
        if (spec.driver) {
            return spec.driver;
        }
        var selector = spec.selector || '';
        if (selector.indexOf('cap-widget') !== -1) {
            return 'cap';
        }
        if (selector.indexOf('turnstile') !== -1) {
            return 'turnstile';
        }
        if (selector.indexOf('h-captcha') !== -1 || selector.indexOf('hcaptcha') !== -1) {
            return 'hcaptcha';
        }
        return '';
    }

    function captchaApiReady() {
        var driver = captchaDriver();
        if (driver === 'cap') {
            return window.Cap || null;
        }
        if (driver === 'turnstile') {
            return window.turnstile && window.turnstile.render ? window.turnstile : null;
        }
        if (driver === 'hcaptcha') {
            return window.hcaptcha && window.hcaptcha.render ? window.hcaptcha : null;
        }
        return window.Cap || window.turnstile || window.hcaptcha || null;
    }

    function widgetAttr(name) {
        var html = (cfg.captcha && cfg.captcha.widget) || '';
        var wrap = document.createElement('div');
        wrap.innerHTML = html;
        var el = cfg.captcha && cfg.captcha.selector ? wrap.querySelector(cfg.captcha.selector) : wrap.firstElementChild;
        if (!el) {
            el = wrap.firstElementChild;
        }
        return el ? (el.getAttribute('data-' + name) || '') : '';
    }

    function stripCaptchaFieldNames(root) {
        if (!root || !cfg.captcha || !cfg.captcha.field) {
            return;
        }
        var nodes = root.querySelectorAll('[name="' + cfg.captcha.field + '"]');
        for (var i = 0; i < nodes.length; i++) {
            nodes[i].removeAttribute('name');
        }
    }

    function loadCaptchaScript() {
        var spec = cfg.captcha || {};
        var ready = captchaApiReady();
        if (ready) {
            return Promise.resolve(ready);
        }
        if (!spec.script) {
            return Promise.reject(new Error('Captcha unavailable'));
        }
        if (captchaScriptLoading) {
            return captchaScriptLoading;
        }
        captchaScriptLoading = new Promise(function (resolve, reject) {
            // The captcha hook may already have injected the same widget script; never load it twice.
            var started = Date.now();
            var existing = document.querySelector('script[src="' + spec.script + '"]');
            if (!existing) {
                existing = document.createElement('script');
                existing.src = spec.script;
                if (spec.scriptAsync !== false) {
                    existing.async = true;
                    existing.defer = true;
                }
                document.head.appendChild(existing);
            }
            (function wait() {
                var api = captchaApiReady();
                if (api) {
                    resolve(api);
                } else if (Date.now() - started > 15000) {
                    reject(new Error('Captcha widget unavailable'));
                } else {
                    setTimeout(wait, 100);
                }
            })();
        });
        captchaScriptLoading.catch(function () {
            captchaScriptLoading = null;
        });
        return captchaScriptLoading;
    }

    function solveCap() {
        var endpoint = (cfg.captcha && cfg.captcha.endpoint) || widgetAttr('cap-api-endpoint');
        if (!endpoint) {
            return Promise.reject(new Error('Captcha endpoint missing'));
        }
        return loadCaptchaScript().then(function () {
            if (!window.Cap) {
                throw new Error('Cap widget unavailable');
            }
            return new window.Cap({ apiEndpoint: endpoint }).solve();
        }).then(function (solution) {
            if (!solution || !solution.token) {
                throw new Error('Captcha not solved');
            }
            return solution.token;
        });
    }

    function CodeWidget(options) {
        this.mode = options.mode;
        this.sendButton = options.sendButton;
        this.verifyButton = options.verifyButton || null;
        this.codeInput = options.codeInput;
        this.messageBox = options.messageBox;
        this.getEmail = options.getEmail || function () { return ''; };
        this.onVerified = options.onVerified;
        this.captchaBox = options.captchaBox || null;
        this.timer = null;
        this.sending = false;
        this.verifying = false;
        this.sentTo = null;
        this.captchaToken = '';
        this.captchaWidgetId = null;
        this.captchaReady = null;
        this.captchaWaiters = [];
        this.bind();
        if (this.mode === 'register' && cfg.captcha && cfg.captcha.script && captchaDriver() !== 'cap') {
            this.mountWidgetCaptcha();
        }
    }

    CodeWidget.prototype.bind = function () {
        var self = this;
        this.sendButton.addEventListener('click', function () {
            self.send();
        });
        if (this.verifyButton) {
            this.verifyButton.addEventListener('click', function () {
                self.verify();
            });
        }
        this.codeInput.addEventListener('input', function () {
            var value = self.codeInput.value.replace(/\D/g, '').slice(0, cfg.codeLength);
            if (value !== self.codeInput.value) {
                self.codeInput.value = value;
            }
            if (value.length === cfg.codeLength) {
                self.verify();
            }
        });
        this.codeInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                self.verify();
            }
        });
    };

    CodeWidget.prototype.message = function (text, type) {
        this.messageBox.textContent = text || '';
        this.messageBox.className = 'ev-msg' + (text && type ? ' is-' + type : '');
    };

    CodeWidget.prototype.sendIdle = function () {
        this.sendButton.disabled = false;
        this.sendButton.textContent = t.btn_send;
    };

    CodeWidget.prototype.cooldown = function (seconds) {
        var self = this;
        var until = Date.now() + Math.max(0, parseInt(seconds, 10) || 0) * 1000;
        clearInterval(this.timer);
        this.timer = null;

        function tick() {
            var left = Math.ceil((until - Date.now()) / 1000);
            if (left <= 0) {
                clearInterval(self.timer);
                self.timer = null;
                self.sendIdle();
                return;
            }
            self.sendButton.disabled = true;
            self.sendButton.textContent = format(t.btn_resend_in, { seconds: left });
        }

        tick();
        if (Date.now() < until) {
            this.timer = setInterval(tick, 500);
        }
    };

    CodeWidget.prototype.flushCaptchaWaiters = function (err, token) {
        var waiters = this.captchaWaiters;
        this.captchaWaiters = [];
        for (var i = 0; i < waiters.length; i++) {
            waiters[i](err, token);
        }
    };

    CodeWidget.prototype.mountWidgetCaptcha = function () {
        var self = this;
        if (self.captchaReady) {
            return self.captchaReady;
        }
        self.captchaReady = loadCaptchaScript().then(function () {
            var driver = captchaDriver();
            var box = self.captchaBox;
            if (!box || driver === 'cap') {
                return;
            }
            var sitekey = (cfg.captcha && cfg.captcha.sitekey) || widgetAttr('sitekey');
            var theme = (cfg.captcha && cfg.captcha.theme) || widgetAttr('theme') || 'auto';
            if (!sitekey) {
                throw new Error('Captcha sitekey missing');
            }
            box.hidden = false;
            box.innerHTML = '';
            var host = document.createElement('div');
            host.className = 'ev-captcha-challenge';
            box.appendChild(host);
            if (driver === 'turnstile' && window.turnstile && window.turnstile.render) {
                self.captchaWidgetId = window.turnstile.render(host, {
                    sitekey: sitekey,
                    theme: theme,
                    'response-field': false,
                    callback: function (token) {
                        self.captchaToken = token;
                        stripCaptchaFieldNames(box);
                        self.flushCaptchaWaiters(null, token);
                    },
                    'expired-callback': function () {
                        self.captchaToken = '';
                    }
                });
                stripCaptchaFieldNames(box);
                return;
            }
            if (driver === 'hcaptcha' && window.hcaptcha && window.hcaptcha.render) {
                self.captchaWidgetId = window.hcaptcha.render(host, {
                    sitekey: sitekey,
                    theme: theme === 'auto' ? 'light' : theme,
                    callback: function (token) {
                        self.captchaToken = token;
                        stripCaptchaFieldNames(box);
                        self.flushCaptchaWaiters(null, token);
                    },
                    'expired-callback': function () {
                        self.captchaToken = '';
                    }
                });
                stripCaptchaFieldNames(box);
                return;
            }
            throw new Error('Captcha driver unavailable');
        });
        self.captchaReady.catch(function () {
            self.captchaReady = null;
        });
        return self.captchaReady;
    };

    CodeWidget.prototype.resetCaptchaWidget = function () {
        var driver = captchaDriver();
        try {
            if (driver === 'turnstile' && window.turnstile && this.captchaWidgetId != null) {
                window.turnstile.reset(this.captchaWidgetId);
                stripCaptchaFieldNames(this.captchaBox);
            }
            if (driver === 'hcaptcha' && window.hcaptcha && this.captchaWidgetId != null) {
                window.hcaptcha.reset(this.captchaWidgetId);
                stripCaptchaFieldNames(this.captchaBox);
            }
        } catch (e) {}
    };

    CodeWidget.prototype.takeCaptchaToken = function (token) {
        token = token || this.captchaToken;
        this.captchaToken = '';
        this.resetCaptchaWidget();
        if (!token) {
            throw new Error('Captcha not solved');
        }
        return token;
    };

    CodeWidget.prototype.waitCaptchaToken = function (timeout) {
        var self = this;
        if (self.captchaToken) {
            return Promise.resolve(self.captchaToken);
        }
        return new Promise(function (resolve, reject) {
            var waiter = function (err, token) {
                clearTimeout(timer);
                if (err) {
                    reject(err);
                } else {
                    resolve(token);
                }
            };
            var timer = setTimeout(function () {
                self.captchaWaiters = self.captchaWaiters.filter(function (fn) {
                    return fn !== waiter;
                });
                reject(new Error('Captcha timeout'));
            }, timeout);
            self.captchaWaiters.push(waiter);
        });
    };

    CodeWidget.prototype.solveCaptcha = function () {
        var self = this;
        if (!cfg.captcha) {
            return Promise.resolve('');
        }
        if (captchaDriver() === 'cap') {
            return solveCap();
        }
        return self.mountWidgetCaptcha().then(function () {
            if (self.captchaToken) {
                return self.captchaToken;
            }
            return self.waitCaptchaToken(20000);
        }).then(function (token) {
            return self.takeCaptchaToken(token);
        });
    };

    CodeWidget.prototype.send = function () {
        var self = this;
        var payload = {};
        if (this.sending || this.timer) {
            return;
        }
        if (this.mode === 'register') {
            payload.email = this.getEmail().trim();
            if (!EMAIL_RE.test(payload.email)) {
                this.message(t.msg_invalid_email, 'error');
                return;
            }
        }

        this.sending = true;
        this.sendButton.disabled = true;

        var ready = Promise.resolve();
        if (this.mode === 'register' && cfg.captcha) {
            this.sendButton.textContent = t.btn_captcha;
            ready = this.solveCaptcha().then(function (token) {
                payload.cap_token = token;
            });
        }

        ready.then(function () {
            self.sendButton.textContent = t.btn_sending;
            return request(self.mode + '_send', payload);
        }, function () {
            return { ok: false, message: t.msg_captcha_failed };
        }).then(function (res) {
            self.sending = false;
            if (res.verified) {
                self.sendIdle();
                self.onVerified(res);
                return;
            }
            self.message(res.message, res.ok ? 'success' : 'error');
            if (res.cooldown > 0) {
                self.cooldown(res.cooldown);
            } else {
                self.sendIdle();
            }
            if (res.ok) {
                self.sentTo = normalize(payload.email);
                self.codeInput.focus();
            }
            if (res.expired) {
                self.reloadSoon();
            }
        });
    };

    CodeWidget.prototype.verify = function () {
        var self = this;
        var payload = { code: this.codeInput.value.trim() };
        if (this.verifying) {
            return;
        }
        if (!payload.code) {
            this.message(t.msg_enter_code, 'error');
            this.codeInput.focus();
            return;
        }
        if (this.mode === 'register') {
            payload.email = this.getEmail().trim();
        }

        this.setVerifying(true);
        request(this.mode + '_verify', payload).then(function (res) {
            self.setVerifying(false);
            if (res.ok) {
                self.onVerified(res);
                return;
            }
            self.message(res.message, 'error');
            self.codeInput.select();
            if (res.expired) {
                self.reloadSoon();
            }
        });
    };

    CodeWidget.prototype.setVerifying = function (busy) {
        this.verifying = busy;
        if (this.verifyButton) {
            this.verifyButton.disabled = busy;
            this.verifyButton.textContent = busy ? t.btn_verifying : t.btn_verify;
        } else if (busy) {
            this.message(t.btn_verifying, 'info');
        }
    };

    CodeWidget.prototype.lock = function () {
        clearInterval(this.timer);
        this.timer = null;
        this.sendButton.disabled = true;
        this.codeInput.disabled = true;
        if (this.verifyButton) {
            this.verifyButton.disabled = true;
        }
    };

    CodeWidget.prototype.reloadSoon = function () {
        setTimeout(function () {
            window.location.reload();
        }, 1500);
    };

    function initPage(root) {
        var widget = new CodeWidget({
            mode: 'login',
            sendButton: root.querySelector('[data-ev-send]'),
            verifyButton: root.querySelector('[data-ev-verify]'),
            codeInput: root.querySelector('[data-ev-code]'),
            messageBox: root.querySelector('[data-ev-msg]'),
            onVerified: function (res) {
                widget.lock();
                widget.message(res.message, 'success');
                if (res.redirect) {
                    setTimeout(function () {
                        window.location.href = res.redirect;
                    }, 1000);
                }
            }
        });

        if (cfg.cooldown > 0) {
            widget.cooldown(cfg.cooldown);
        } else if (cfg.autoSend) {
            widget.send();
        }
        widget.codeInput.focus();
    }

    var inlineEntries = [];

    function mountInline(emailInput) {
        var anchor = emailInput.closest('.form-group') || emailInput.parentNode;
        var box = document.createElement('div');
        box.className = 'ev-inline';
        box.innerHTML =
            '<div class="ev-row" data-ev-row>' +
                '<input type="text" class="form-control" data-ev-code inputmode="numeric" autocomplete="one-time-code">' +
                '<button type="button" class="btn btn-default btn-outline-secondary" data-ev-send></button>' +
            '</div>' +
            '<div class="ev-captcha" data-ev-captcha hidden></div>' +
            '<div class="ev-msg" data-ev-msg role="status" aria-live="polite"></div>' +
            '<div class="ev-done" data-ev-done hidden></div>';
        anchor.parentNode.insertBefore(box, anchor.nextSibling);

        var row = box.querySelector('[data-ev-row]');
        var codeInput = box.querySelector('[data-ev-code]');
        var sendButton = box.querySelector('[data-ev-send]');
        var done = box.querySelector('[data-ev-done]');
        codeInput.placeholder = format(t.code_placeholder, { length: cfg.codeLength });
        codeInput.maxLength = cfg.codeLength;
        sendButton.textContent = t.btn_send;
        done.textContent = t.msg_verified_inline;

        var entry = { form: emailInput.form, box: box, verifiedEmail: null };
        var verified = cfg.verified || (cfg.verified = []);

        var widget = new CodeWidget({
            mode: 'register',
            sendButton: sendButton,
            codeInput: codeInput,
            messageBox: box.querySelector('[data-ev-msg]'),
            captchaBox: box.querySelector('[data-ev-captcha]'),
            getEmail: function () {
                return emailInput.value;
            },
            onVerified: function () {
                var email = normalize(emailInput.value);
                if (verified.indexOf(email) < 0) {
                    verified.push(email);
                }
                markVerified(email);
            }
        });
        entry.widget = widget;

        function markVerified(email) {
            entry.verifiedEmail = email;
            row.hidden = true;
            done.hidden = false;
            codeInput.value = '';
            widget.message('');
            if (widget.captchaBox) {
                widget.captchaBox.hidden = true;
            }
        }

        function sync() {
            var email = normalize(emailInput.value);
            if (verified.indexOf(email) >= 0) {
                if (entry.verifiedEmail !== email) {
                    markVerified(email);
                }
                return;
            }
            if (entry.verifiedEmail !== null || (widget.sentTo && widget.sentTo !== email)) {
                entry.verifiedEmail = null;
                widget.sentTo = null;
                row.hidden = false;
                done.hidden = true;
                codeInput.value = '';
                widget.message('');
                if (widget.captchaBox && cfg.captcha && cfg.captcha.script && captchaDriver() !== 'cap') {
                    widget.captchaBox.hidden = false;
                }
            }
        }

        emailInput.addEventListener('input', sync);
        emailInput.addEventListener('change', sync);
        sync();
        inlineEntries.push(entry);
    }

    function guardSubmit(event) {
        for (var i = 0; i < inlineEntries.length; i++) {
            var entry = inlineEntries[i];
            if (entry.widget && entry.widget.captchaBox) {
                stripCaptchaFieldNames(entry.widget.captchaBox);
            }
            if (entry.form !== event.target || entry.verifiedEmail !== null || !isVisible(entry.box)) {
                continue;
            }
            event.preventDefault();
            event.stopImmediatePropagation();
            entry.widget.message(t.msg_need_verify, 'error');
            entry.box.scrollIntoView({ block: 'center', behavior: 'smooth' });
            entry.widget.codeInput.focus({ preventScroll: true });
            return;
        }
    }

    function initInline() {
        var inputs = document.querySelectorAll(INLINE_SELECTOR);
        var mounted = [];
        Array.prototype.forEach.call(inputs, function (input) {
            if (input.type !== 'hidden' && mounted.indexOf(input) < 0) {
                mounted.push(input);
                mountInline(input);
            }
        });
        if (mounted.length) {
            document.addEventListener('submit', guardSubmit, true);
        }
    }

    function init() {
        if (cfg.mode === 'login') {
            var root = document.querySelector('[data-ev-page]');
            if (root) {
                initPage(root);
            }
            return;
        }
        initInline();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
