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

        {{-- Scripts --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Livewire Styles --}}
        @livewireStyles
    </head>
    <body class="min-h-screen flex flex-col font-sans text-gray-900 antialiased">
        {{-- Skip to main content for accessibility --}}
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-indigo-600 text-white px-4 py-2 rounded-md z-50">
            Skip to main content
        </a>

        {{-- Main Content --}}
        <main id="main-content" class="flex-grow" role="main" aria-label="Main content">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="bg-white border-t mt-auto" role="contentinfo">
            <div class="max-w-3xl mx-auto px-4 py-6 text-sm text-gray-600 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
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

        {{-- Livewire Scripts --}}
        @livewireScripts
    </body>
</html>
