@extends('layouts.app')

@section('title', __('app.nav.files'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">{{ __('app.files.title') }}</h1>
    <a href="{{ route('upload.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> {{ __('app.files.upload') }}
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="files-table">
            <thead class="table-light">
            <tr>
                <th>{{ __('app.files.col_file') }}</th>
                <th class="text-end">{{ __('app.files.col_size') }}</th>
                <th>{{ __('app.files.col_uploaded') }}</th>
                <th>{{ __('app.files.col_expires') }}</th>
                <th class="text-end">{{ __('app.files.col_actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($files as $file)
                <tr data-id="{{ $file->id }}">
                    <td>
                        <span class="badge text-bg-{{ $file->extension === 'pdf' ? 'danger' : 'primary' }} me-1 text-uppercase">{{ $file->extension }}</span>
                        <span class="text-break">{{ $file->original_name }}</span>
                    </td>
                    <td class="text-end text-nowrap">{{ $file->humanSize() }}</td>
                    <td class="text-nowrap" data-datetime="{{ $file->created_at->toIso8601String() }}"></td>
                    <td class="text-nowrap" data-countdown="{{ $file->expires_at->toIso8601String() }}"></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('files.download', $file) }}" class="btn btn-sm btn-outline-secondary js-download" title="{{ __('app.files.download') }}">
                            <i class="bi bi-download"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger js-delete"
                                data-url="{{ route('files.destroy', $file) }}"
                                data-name="{{ $file->original_name }}" title="{{ __('app.files.delete') }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr class="js-empty">
                    <td colspan="5" class="text-center text-secondary py-5">
                        {{ __('app.files.empty') }}
                        <a href="{{ route('upload.create') }}">{{ __('app.files.empty_link') }}</a>.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $files->links() }}</div>

{{-- Подтверждение удаления --}}
<div class="modal fade" id="delete-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('app.files.delete_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Имя файла подставляет files.js, в перевод попадает только пустой тег --}}
                {!! __('app.files.delete_body', ['name' => '<strong class="js-delete-name text-break"></strong>']) !!}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('app.files.cancel') }}</button>
                <button type="button" class="btn btn-danger js-confirm-delete">
                    <span class="spinner-border spinner-border-sm d-none"></span> {{ __('app.files.delete') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/files.js') }}"></script>
@endpush
