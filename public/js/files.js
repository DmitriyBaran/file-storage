/* CRUD-страница: обратный отсчёт и удаление через AJAX */
(function ($) {
    'use strict';

    const modalEl = document.getElementById('delete-modal');
    const modal = new bootstrap.Modal(modalEl);
    const $confirm = $(modalEl).find('.js-confirm-delete');
    let pending = null; // {url, $row, name}

    // Даты рендерим в часовом поясе браузера
    $('[data-datetime]').each(function () {
        $(this).text(new Date($(this).data('datetime')).toLocaleString(App.locale));
    });

    function pad(n) { return String(n).padStart(2, '0'); }

    let syncTimer = null;

    // Сервер удаляет просроченные файлы раз в минуту — сверяемся с ним и убираем исчезнувшие строки
    function syncExpiredRows() {
        $.get(window.location.href, null, null, 'html').done(function (html) {
            const alive = new Set(
                $($.parseHTML(html)).find('#files-table tr[data-id]')
                    .map(function () { return String($(this).data('id')); }).get()
            );

            $('#files-table tr[data-id]').each(function () {
                const $row = $(this);
                if (alive.has(String($row.data('id')))) return;

                App.toast(App.t('expired_removed', {name: $row.find('.js-delete').data('name')}), 'secondary');
                $row.removeAttr('data-id').fadeOut(200, function () {
                    $(this).remove();
                    ensureEmptyState();
                });
            });
        });
    }

    function tickCountdowns() {
        const now = Date.now();
        let hasExpired = false;

        $('[data-countdown]').each(function () {
            const $cell = $(this);
            const left = Math.floor((new Date($cell.data('countdown')).getTime() - now) / 1000);

            if (left <= 0) {
                hasExpired = true;
                $cell.html($('<span class="badge text-bg-secondary">').text(App.t('deleting')));
                $cell.closest('tr').find('.js-download').addClass('disabled').attr('aria-disabled', 'true');
                return;
            }

            const h = Math.floor(left / 3600);
            const m = Math.floor((left % 3600) / 60);
            const s = left % 60;
            $cell.text(h + ':' + pad(m) + ':' + pad(s))
                .toggleClass('text-danger fw-semibold', left < 3600);
        });

        if (hasExpired && !syncTimer) {
            syncTimer = setInterval(syncExpiredRows, 5000);
        } else if (!hasExpired && syncTimer) {
            clearInterval(syncTimer);
            syncTimer = null;
        }
    }

    tickCountdowns();
    setInterval(tickCountdowns, 1000);

    function ensureEmptyState() {
        const $tbody = $('#files-table tbody');
        if ($tbody.find('tr[data-id]').length === 0) {
            $tbody.append(
                '<tr class="js-empty"><td colspan="5" class="text-center text-secondary py-5">' +
                App.escapeHtml(App.t('empty')) + ' <a href="/upload">' + App.escapeHtml(App.t('empty_link')) + '</a>.</td></tr>'
            );
        }
    }

    $(document).on('click', '.js-delete', function () {
        const $btn = $(this);
        pending = {url: $btn.data('url'), name: $btn.data('name'), $row: $btn.closest('tr')};
        $(modalEl).find('.js-delete-name').text(pending.name);
        modal.show();
    });

    $confirm.on('click', function () {
        if (!pending) return;

        const current = pending;
        $confirm.prop('disabled', true).find('.spinner-border').removeClass('d-none');

        $.ajax({url: current.url, method: 'DELETE'})
            .done(function (response) {
                modal.hide();
                current.$row.fadeOut(200, function () {
                    $(this).remove();
                    ensureEmptyState();
                });
                App.toast(response.message, 'success');
            })
            .fail(function (xhr) {
                modal.hide();
                if (xhr.status === 404) current.$row.remove();
                App.toast(App.errorMessage(xhr), 'danger');
            })
            .always(function () {
                $confirm.prop('disabled', false).find('.spinner-border').addClass('d-none');
                pending = null;
            });
    });
})(jQuery);
