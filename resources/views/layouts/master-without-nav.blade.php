<!DOCTYPE html>
<html lang="de" dir="ltr">

<head>
    @include('layouts.metahead')
    @php($pageTitle = trim((string) ($title ?? $__env->yieldContent('title'))))
    <title>{{ $pageTitle !== '' ? $pageTitle.' | ' : '' }}Lotto</title>
    <link rel="icon" type="image/png" href="{{ asset('/site-images/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('/site-images/favicon/apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('/site-images/favicon/favicon.ico') }}">
    <!-- css -->
    @include('layouts.head-css')
    @livewireStyles
</head>

<body data-mode="light" data-sidebar-size="lg" class="group">
    
    @include('layouts.no-auth-layout')
    <!-- script -->
    @include('layouts.vendor-scripts')
    <!-- Scripts -->
    @vite(['resources/js/app.js'])
    @livewireScripts
</body>

</html>
