/* Общие хелперы */
window.App = (function ($) {
    'use strict';

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    // Язык страницы — для дат и подписей
    const locale = document.documentElement.lang || 'en';

    /** Перевод из window.I18n с подстановкой :параметров, как __() в Laravel */
    function t(key, params) {
        let text = (window.I18n || {})[key] || key;
        $.each(params || {}, function (name, value) {
            text = text.split(':' + name).join(value);
        });
        return text;
    }

    function escapeHtml(str) {
        return $('<div>').text(str).html();
    }

    function toast(message, type) {
        const $toast = $(
            '<div class="toast align-items-center border-0 text-bg-' + (type || 'success') + '" role="alert">' +
            '  <div class="d-flex">' +
            '    <div class="toast-body"></div>' +
            '    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '  </div>' +
            '</div>'
        );
        $toast.find('.toast-body').text(message);
        $('#toasts').append($toast);
        const instance = new bootstrap.Toast($toast[0], {delay: 4000});
        $toast.on('hidden.bs.toast', function () { $toast.remove(); });
        instance.show();
    }

    /** Достаёт человекочитаемую ошибку из ответа Laravel */
    function errorMessage(xhr) {
        if (xhr.status === 413) return t('error_413');
        if (xhr.status === 419) return t('error_419');
        if (xhr.status === 404) return t('error_404');

        const json = xhr.responseJSON || {};
        if (json.errors) {
            const first = Object.values(json.errors)[0];
            if (first && first.length) return first[0];
        }
        return json.message || t('error_server', {status: xhr.status});
    }

    function formatSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let i = 0;
        while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
        return (i === 0 ? bytes : bytes.toFixed(1)) + ' ' + units[i];
    }

    return {locale: locale, t: t, escapeHtml: escapeHtml, toast: toast, errorMessage: errorMessage, formatSize: formatSize};
})(jQuery);
