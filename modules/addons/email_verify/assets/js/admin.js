(function () {
    'use strict';

    var root = document.getElementById('ev-admin');
    var cfg = window.EmailVerifyAdmin;
    if (!root || !cfg) {
        return;
    }

    var t = cfg.lang || {};
    var state = { page: 1, pages: 1, q: '', status: 'all' };
    var requestId = 0;
    var alertTimer = null;

    var tbody = root.querySelector('[data-ev-rows]');
    var checkAll = root.querySelector('[data-ev-check-all]');
    var alertBox = root.querySelector('[data-ev-alert]');
    var pageInfo = root.querySelector('[data-ev-page-info]');
    var prevButton = root.querySelector('[data-ev-prev]');
    var nextButton = root.querySelector('[data-ev-next]');
    var searchInput = root.querySelector('[data-ev-search]');
    var statusSelect = root.querySelector('[data-ev-status]');
    var bulkButtons = root.querySelectorAll('[data-ev-bulk]');
    var verifyAllButton = root.querySelector('[data-ev-verify-all]');

    function format(text, vars) {
        return String(text || '').replace(/\{(\w+)\}/g, function (match, key) {
            return vars && vars[key] !== undefined ? vars[key] : match;
        });
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined && text !== null) {
            node.textContent = text;
        }
        return node;
    }

    function cell(content, className) {
        var td = el('td', className);
        if (content instanceof Node) {
            td.appendChild(content);
        } else {
            td.textContent = content;
        }
        return td;
    }

    function call(action, pairs, method) {
        var params = new URLSearchParams();
        params.append('action', action);
        (pairs || []).forEach(function (pair) {
            params.append(pair[0], pair[1]);
        });
        var url = cfg.endpoint;
        var options = {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        };
        if (method === 'POST') {
            params.append('token', cfg.token);
            options.method = 'POST';
            options.body = params;
        } else {
            url += (url.indexOf('?') < 0 ? '?' : '&') + params.toString();
        }
        return fetch(url, options).then(function (response) {
            return response.json();
        }).catch(function () {
            return { ok: false, message: t.admin_request_failed };
        });
    }

    function notify(message, type) {
        clearTimeout(alertTimer);
        alertBox.textContent = '';
        if (!message) {
            return;
        }
        alertBox.appendChild(el('div', 'alert alert-' + type, message));
        alertTimer = setTimeout(function () {
            alertBox.textContent = '';
        }, 4000);
    }

    function selectedIds() {
        return Array.prototype.map.call(tbody.querySelectorAll('[data-ev-check]:checked'), function (box) {
            return box.value;
        });
    }

    function updateBulk() {
        var any = selectedIds().length > 0;
        Array.prototype.forEach.call(bulkButtons, function (button) {
            button.disabled = !any;
        });
    }

    function load() {
        var id = ++requestId;
        tbody.classList.add('is-loading');
        call('list', [['page', state.page], ['q', state.q], ['status', state.status]]).then(function (res) {
            if (id !== requestId) {
                return;
            }
            tbody.classList.remove('is-loading');
            if (!res.ok) {
                notify(res.message || t.admin_request_failed, 'danger');
                return;
            }
            state.page = res.page;
            state.pages = res.pages;
            renderStats(res.stats);
            renderRows(res.rows);
            renderPager(res);
        });
    }

    function renderStats(stats) {
        Object.keys(stats).forEach(function (key) {
            var node = root.querySelector('[data-ev-stat="' + key + '"]');
            if (node) {
                node.textContent = stats[key];
            }
        });
    }

    function renderRows(rows) {
        tbody.textContent = '';
        checkAll.checked = false;

        if (!rows.length) {
            var empty = cell(t.admin_no_results, 'text-center text-muted');
            empty.colSpan = 8;
            var tr = el('tr');
            tr.appendChild(empty);
            tbody.appendChild(tr);
            updateBulk();
            return;
        }

        rows.forEach(function (row) {
            var tr = el('tr');
            var verified = !!row.verified_at;

            var checkbox = el('input');
            checkbox.type = 'checkbox';
            checkbox.value = row.id;
            checkbox.setAttribute('data-ev-check', '');
            tr.appendChild(cell(checkbox));

            tr.appendChild(cell(String(row.id)));
            tr.appendChild(cell(row.name || '-'));
            tr.appendChild(cell(row.email));

            var clients = el('td');
            if (!row.clients.length) {
                clients.textContent = '-';
            }
            row.clients.forEach(function (client, index) {
                var link = el('a', null, '#' + client.id);
                link.href = 'clientssummary.php?userid=' + encodeURIComponent(client.id);
                if (client.owner) {
                    link.title = t.admin_owner;
                    link.style.fontWeight = 'bold';
                }
                if (index) {
                    clients.appendChild(document.createTextNode(' '));
                }
                clients.appendChild(link);
            });
            tr.appendChild(clients);

            var badge = el('span', 'label ' + (verified ? 'label-success' : 'label-danger'),
                verified ? t.admin_status_verified : t.admin_status_unverified);
            if (verified) {
                badge.title = row.verified_at;
            }
            tr.appendChild(cell(badge));
            tr.appendChild(cell(row.created_at || '-'));

            var toggle = el('button', 'btn btn-xs ' + (verified ? 'btn-default' : 'btn-success'),
                verified ? t.admin_mark_unverified : t.admin_mark_verified);
            toggle.type = 'button';
            toggle.addEventListener('click', function () {
                if (verified && !window.confirm(t.admin_confirm_unverify)) {
                    return;
                }
                update([row.id], !verified, toggle);
            });
            tr.appendChild(cell(toggle, 'text-right'));

            tbody.appendChild(tr);
        });
        updateBulk();
    }

    function renderPager(res) {
        pageInfo.textContent = format(t.admin_page_info, { page: res.page, pages: res.pages, total: res.total });
        prevButton.disabled = res.page <= 1;
        nextButton.disabled = res.page >= res.pages;
    }

    function update(ids, verified, button) {
        if (button) {
            button.disabled = true;
        }
        var pairs = [['verified', verified ? 1 : 0]];
        ids.forEach(function (id) {
            pairs.push(['ids[]', id]);
        });
        call('set', pairs, 'POST').then(function (res) {
            notify(res.message || t.admin_request_failed, res.ok ? 'success' : 'danger');
            load();
        });
    }

    var searchTimer = null;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            state.q = searchInput.value.trim();
            state.page = 1;
            load();
        }, 300);
    });

    statusSelect.addEventListener('change', function () {
        state.status = statusSelect.value;
        state.page = 1;
        load();
    });

    prevButton.addEventListener('click', function () {
        if (state.page > 1) {
            state.page -= 1;
            load();
        }
    });

    nextButton.addEventListener('click', function () {
        if (state.page < state.pages) {
            state.page += 1;
            load();
        }
    });

    checkAll.addEventListener('change', function () {
        Array.prototype.forEach.call(tbody.querySelectorAll('[data-ev-check]'), function (box) {
            box.checked = checkAll.checked;
        });
        updateBulk();
    });

    tbody.addEventListener('change', function (event) {
        if (event.target.hasAttribute('data-ev-check')) {
            updateBulk();
        }
    });

    Array.prototype.forEach.call(bulkButtons, function (button) {
        button.addEventListener('click', function () {
            var ids = selectedIds();
            if (!ids.length) {
                notify(t.admin_select_first, 'warning');
                return;
            }
            var verified = button.getAttribute('data-ev-bulk') === '1';
            if (!verified && !window.confirm(t.admin_confirm_unverify)) {
                return;
            }
            update(ids, verified, null);
        });
    });

    verifyAllButton.addEventListener('click', function () {
        if (!window.confirm(t.admin_confirm_verify_all)) {
            return;
        }
        verifyAllButton.disabled = true;
        call('set_all', [], 'POST').then(function (res) {
            verifyAllButton.disabled = false;
            notify(res.message || t.admin_request_failed, res.ok ? 'success' : 'danger');
            load();
        });
    });

    load();
})();
