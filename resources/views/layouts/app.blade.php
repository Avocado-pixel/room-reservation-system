<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" prefix="og: https://ogp.me/ns#">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Dynamic SEO Meta Tags --}}
        <x-seo-head />

        {{-- Fonts with display swap for Core Web Vitals --}}
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- Favicon --}}
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        {{-- Scripts with defer for non-blocking load --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>[x-cloak]{display:none !important;}</style>

        {{-- Livewire Styles --}}
        @livewireStyles
    </head>
    <body class="font-sans antialiased min-h-screen flex flex-col">
        {{-- Skip to main content for accessibility --}}
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-indigo-600 text-white px-4 py-2 rounded-md z-50">
            Skip to main content
        </a>

        <x-banner />

        <div class="flex-grow bg-gray-100">
            {{-- Navigation --}}
            <nav role="navigation" aria-label="Main navigation">
                @livewire('navigation-menu')
            </nav>

            {{-- Page Heading --}}
            @if (isset($header))
                <header class="bg-white shadow" role="banner">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            {{-- Main Content --}}
            <main id="main-content" role="main" aria-label="Main content">
                {{ $slot }}
            </main>
        </div>

        {{-- Footer --}}
        <footer class="bg-white border-t mt-auto" role="contentinfo">
            <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 text-sm text-gray-600 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <span itemscope itemtype="https://schema.org/Organization">
                        <span itemprop="name">{{ config('app.name', 'SAW') }}</span>
                    </span> &copy; {{ now()->year }}
                </div>
                <nav aria-label="Footer navigation" class="flex items-center gap-4">
                    <a href="{{ route('policy.show') }}" class="underline hover:text-gray-900">Privacy Policy</a>
                    <a href="{{ route('terms.show') }}" class="underline hover:text-gray-900">Terms & Conditions</a>
                </nav>
            </div>
        </footer>

        @stack('modals')

        {{-- Livewire Scripts --}}
        @livewireScripts
    </body>
</html>
