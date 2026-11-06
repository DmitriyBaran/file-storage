@extends('layouts.app')

@section('title', __('app.upload.title'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3">{{ __('app.upload.title') }}</h1>
        <p class="text-secondary">
            {{ __('app.upload.hint', [
                'types' => mb_strtoupper(implode(', ', $extensions)),
                'size' => round($maxSizeKb / 1024, 1),
                'hours' => $ttlHours,
            ]) }}
        </p>

        <form id="upload-form"
              action="{{ route('upload.store') }}"
              method="POST"
              enctype="multipart/form-data"
              data-max-size="{{ $maxSizeKb * 1024 }}"
              data-extensions="{{ implode(',', $extensions) }}">
            @csrf

            <label for="file-input" id="dropzone" class="dropzone card border-2 text-center p-5 mb-3">
                <i class="bi bi-cloud-arrow-up display-4 text-primary"></i>
                <span class="fs-5 mt-2">{{ __('app.upload.dropzone') }}</span>
                <span class="text-secondary small">{{ __('app.upload.dropzone_hint') }}</span>
                <input type="file" id="file-input" name="file" class="d-none" multiple
                       accept="{{ collect($extensions)->map(fn ($e) => '.'.$e)->implode(',') }}">
            </label>

            <noscript>
                <button type="submit" class="btn btn-primary">{{ __('app.upload.submit') }}</button>
            </noscript>
        </form>

        <ul id="upload-list" class="list-group mb-3"></ul>

        <a href="{{ route('files.index') }}" class="btn btn-outline-primary d-none" id="go-to-files">
            <i class="bi bi-folder2-open"></i> {{ __('app.upload.go_to_files') }}
        </a>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/upload.js') }}"></script>
@endpush
