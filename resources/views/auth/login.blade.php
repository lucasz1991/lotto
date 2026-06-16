@extends('layouts.master-without-nav')

@section('title')
    Login
@endsection

@section('css')
    <link rel="stylesheet" href="{{ URL::asset('build/libs/swiper/swiper-bundle.min.css') }}">
@endsection

@section('content')
    <div class="my-auto">
        <div class="text-center">
            <div class="mx-auto mb-6 flex justify-center">
                <img src="{{ asset('/site-images/logo.png') }}" alt="Lotto" class="h-16 w-auto">
            </div>

            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-500">
                Admin-Bereich
            </p>
            <h1 class="mt-3 text-2xl font-bold text-gray-800 dark:text-gray-100">
                Lotto Projekt
            </h1>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-100/70">
                Melde dich an, um Einstellungen, Historie und Empfehlungen zu verwalten.
            </p>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" x-data="{ submitting: false }" x-on:submit="submitting = true">
            @csrf

            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-100">
                    E-Mail <span class="text-red-600">*</span>
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="admin@example.com"
                    autocomplete="email"
                    required
                    autofocus
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-zinc-600 dark:bg-zinc-700/50 dark:text-gray-100"
                >
                @error('email')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-100">
                        Passwort <span class="text-red-600">*</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-300">
                            Passwort vergessen?
                        </a>
                    @endif
                </div>

                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Passwort eingeben"
                    autocomplete="current-password"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-zinc-600 dark:bg-zinc-700/50 dark:text-gray-100"
                >
                @error('password')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label for="remember" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-100">
                    <input
                        id="remember"
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        checked
                    >
                    <span>Angemeldet bleiben</span>
                </label>
            </div>

            <x-button
                type="submit"
                class="w-full justify-center rounded-lg border-transparent  py-3"
            >
                Einloggen
            </x-button>

        </form>
    </div>
@endsection
