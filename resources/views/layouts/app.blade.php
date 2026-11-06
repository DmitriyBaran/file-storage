<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand bg-white border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ route('upload.create') }}">
            <i class="bi bi-hourglass-split text-primary"></i> {{ config('app.name') }}
        </a>
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('upload.*') ? 'active fw-semibold' : '' }}"
                   href="{{ route('upload.create') }}"><i class="bi bi-cloud-arrow-up"></i> {{ __('app.nav.upload') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('files.*') ? 'active fw-semibold' : '' }}"
                   href="{{ route('files.index') }}"><i class="bi bi-folder2-open"></i> {{ __('app.nav.files') }}</a>
            </li>
            <li class="nav-item dropdown ms-lg-2">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                   aria-expanded="false" title="{{ __('app.nav.language') }}">
                    <i class="bi bi-translate"></i> {{ strtoupper(app()->getLocale()) }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach (config('app.available_locales') as $code => $label)
                        <li>
                            <a class="dropdown-item {{ app()->isLocale($code) ? 'active' : '' }}"
                               href="{{ route('locale.switch', $code) }}">{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </li>
        </ul>
    </div>
</nav>

<main class="container pb-5">
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</main>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toasts"></div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.I18n = @json(__('app.js'));</script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
