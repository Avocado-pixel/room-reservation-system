<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SAW') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style_public.css') }}">
</head>
<body>
    <nav class="w-full bg-gray-900 py-4 px-4 flex justify-between items-center">
        <a href="/" class="text-xl font-bold text-white tracking-tight">{{ config('app.name', 'SAW') }}</a>
        <div class="flex gap-2">
            <a href="{{ route('login') }}" class="inline-block px-5 py-2 rounded-lg font-semibold text-white bg-gray-800 hover:bg-gray-700 transition">Login</a>
            <a href="{{ route('register') }}" class="inline-block px-5 py-2 rounded-lg font-semibold text-white bg-gray-800 hover:bg-gray-700 transition">Register</a>
        </div>
    </nav>
    @yield('content')
</body>
</html>
