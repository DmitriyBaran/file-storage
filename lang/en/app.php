<?php

return [

    'nav' => [
        'upload' => 'Upload',
        'files' => 'Files',
        'language' => 'Language',
    ],

    'upload' => [
        'title' => 'Upload files',
        'hint' => ':types, up to :size MB. Files are kept for :hours h and then deleted automatically.',
        'dropzone' => 'Drop files here or click to choose',
        'dropzone_hint' => 'You can select several files at once',
        'submit' => 'Upload',
        'go_to_files' => 'Go to the file list',
        'uploaded' => 'File uploaded.',
    ],

    'files' => [
        'title' => 'Uploaded files',
        'upload' => 'Upload',
        'col_file' => 'File',
        'col_size' => 'Size',
        'col_uploaded' => 'Uploaded',
        'col_expires' => 'Deleted in',
        'col_actions' => 'Actions',
        'download' => 'Download',
        'delete' => 'Delete',
        'empty' => 'No files yet.',
        'empty_link' => 'Upload the first one',
        'delete_title' => 'Delete file?',
        'delete_body' => 'File :name will be deleted permanently.',
        'cancel' => 'Cancel',
        'deleted' => 'File “:name” deleted.',
    ],

    'validation' => [
        'required' => 'Choose a file to upload.',
        'file' => 'The file was not uploaded.',
        'uploaded' => 'The file failed to upload. It may be larger than :size MB.',
        'types' => 'Allowed file types: :types.',
        'max' => 'Maximum file size is :size MB.',
        'name_too_long' => 'The file name must not be longer than :max characters.',
    ],

    'errors' => [
        'store_failed' => 'Could not save the file to disk.',
        'not_on_disk' => 'File not found on disk.',
        'expired' => 'The storage period of this file has expired.',
    ],

    'reason' => [
        'manual' => 'was deleted manually',
        'expired' => 'was deleted automatically after its storage period expired',
    ],

    'notification' => [
        'subject' => 'File deleted: :name',
        'body' => "File “:name” (:size) :reason.\nUploaded: :uploaded\nDeleted: :deleted",
    ],

    // Строки для JS — попадают на страницу через window.I18n
    'js' => [
        'waiting' => 'Waiting…',
        'uploading' => 'Uploading… :percent%',
        'processing' => 'Processing…',
        'uploaded_until' => 'Uploaded. Will be deleted :date.',
        'invalid_type' => 'Allowed file types: :types.',
        'too_large' => 'Maximum file size is :size.',
        'deleting' => 'deleting…',
        'expired_removed' => 'File “:name” was deleted after its storage period expired.',
        'empty' => 'No files.',
        'empty_link' => 'Upload',
        'error_413' => 'The file is too large.',
        'error_419' => 'Session expired, please reload the page.',
        'error_404' => 'File not found (it may have been deleted already).',
        'error_server' => 'Server error (:status).',
    ],

];
