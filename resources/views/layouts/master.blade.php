<!DOCTYPE html>
<html lang="de" dir="ltr">

<head>
    @include('layouts.metahead')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($pageTitle = trim((string) ($title ?? $__env->yieldContent('title'))))
    <title>{{ $pageTitle !== '' ? $pageTitle.' | ' : '' }}Lotto</title>
    <link rel="icon" type="image/png" href="{{ asset('/site-images/icon.png') }}">
    <!-- css files -->
    @include('layouts.head-css')
    @vite(['resources/css/app.css'])
    <!-- Styles -->
    @livewireStyles
    @yield('css')
</head>
    <body data-mode="light" data-sidebar-size="lg" class="group font-notosans">
        <!-- sidebar -->
        @include('layouts.sidebar')
        <!-- topbar -->
        @include('layouts.topbar')
        <!-- content -->
        @yield('content')
        <!-- Page Content -->
        @if(isset($slot))
            <main class="bg-gray-100">
                <div class="main-content group-data-[sidebar-size=sm]:ml-[70px]">
                    <div class="min-h-screen page-content px-1" style="box-shadow: inset 0px 80px 30px -10px rgba(0, 0, 0, 0.2);">
                        <div class="container-fluid px-0 md:px-5">
                            <div class=" @if(! request()->routeIs('admin.index', 'admin.recommendations')) bg-white rounded-md border border-gray-200 p-4  @endif ">
                                <x-ui.loading.livewire-indicator />
                                {{ $slot }}
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        @endif
        <!-- script -->
        @include('layouts.vendor-scripts')
        <!-- Scripts -->
        @vite(['resources/js/app.js'])
        @livewireScripts
        @yield('js')
    </body>
</html>
 
