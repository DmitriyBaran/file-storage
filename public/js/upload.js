/* Асинхронная загрузка файлов с прогрессом */
(function ($) {
    'use strict';

    const $form = $('#upload-form');
    const $input = $('#file-input');
    const $dropzone = $('#dropzone');
    const $list = $('#upload-list');
    const maxSize = parseInt($form.data('max-size'), 10);
    const extensions = String($form.data('extensions')).split(',');

    function addItem(file) {
        const $item = $(
            '<li class="list-group-item upload-item">' +
            '  <div class="d-flex justify-content-between gap-2">' +
            '    <span class="text-break js-name"></span>' +
            '    <span class="text-secondary small text-nowrap js-size"></span>' +
            '  </div>' +
            '  <div class="progress mt-2"><div class="progress-bar" style="width:0%"></div></div>' +
            '  <div class="small mt-1 js-status text-secondary"></div>' +
            '</li>'
        );
        $item.find('.js-name').text(file.name);
        $item.find('.js-size').text(App.formatSize(file.size));
        $item.find('.js-status').text(App.t('waiting'));
        $list.prepend($item);
        return $item;
    }

    function setStatus($item, text, cls) {
        $item.find('.js-status').attr('class', 'small mt-1 js-status ' + cls).text(text);
    }

    function fail($item, message) {
        $item.find('.progress-bar').addClass('bg-danger').css('width', '100%');
        setStatus($item, message, 'text-danger');
    }

    /** Быстрая проверка на клиенте; окончательная — в StoreFileRequest на сервере */
    function validate(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (extensions.indexOf(ext) === -1) {
            return App.t('invalid_type', {types: extensions.join(', ').toUpperCase()});
        }
        if (file.size > maxSize) {
            return App.t('too_large', {size: App.formatSize(maxSize)});
        }
        return null;
    }

    function upload(file) {
        const $item = addItem(file);
        const error = validate(file);

        if (error) {
            fail($item, error);
            return;
        }

        const data = new FormData();
        data.append('file', file);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: data,
            processData: false,
            contentType: false,
            xhr: function () {
                const xhr = $.ajaxSettings.xhr();
                xhr.upload.addEventListener('progress', function (e) {
                    if (!e.lengthComputable) return;
                    const percent = Math.round(e.loaded / e.total * 100);
                    $item.find('.progress-bar').css('width', percent + '%');
                    setStatus($item, percent < 100 ? App.t('uploading', {percent: percent}) : App.t('processing'), 'text-secondary');
                });
                return xhr;
            }
        }).done(function (response) {
            $item.find('.progress-bar').addClass('bg-success').css('width', '100%');
            const expires = new Date(response.file.expires_at).toLocaleString(App.locale);
            setStatus($item, App.t('uploaded_until', {date: expires}), 'text-success');
            $('#go-to-files').removeClass('d-none');
        }).fail(function (xhr) {
            fail($item, App.errorMessage(xhr));
        });
    }

    function handleFiles(files) {
        $.each(files, function (_, file) { upload(file); });
    }

    $input.on('change', function () {
        handleFiles(this.files);
        this.value = ''; // позволяет выбрать тот же файл ещё раз
    });

    $dropzone
        .on('dragenter dragover', function (e) {
            e.preventDefault();
            $dropzone.addClass('is-dragover');
        })
        .on('dragleave dragend drop', function (e) {
            e.preventDefault();
            $dropzone.removeClass('is-dragover');
        })
        .on('drop', function (e) {
            handleFiles(e.originalEvent.dataTransfer.files);
        });

    // Отправка формы без JS-загрузчика не нужна — всё идёт через AJAX
    $form.on('submit', function (e) { e.preventDefault(); });
})(jQuery);
