<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SAW') }} — Client</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style_admin.css') }}">
</head>
<body class="admin-area">
    <header class="admin-topbar admin-topbar-fixed">
        <a class="admin-logo" href="{{ route('client.rooms.index') }}">
            <div class="mark"></div>
            <div class="title">SAW — Client Dashboard</div>
        </a>
        <nav class="topbar-nav">
            <a class="nav-link" href="{{ route('client.rooms.index') }}">Rooms</a>
            <a class="nav-link" href="{{ route('client.bookings.index') }}">Bookings</a>
        </nav>
        <div class="topbar-actions">
            <form method="post" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button type="submit" class="btn-top primary">Log out</button>
            </form>
        </div>
    </header>

    <main class="admin-wrap" role="main">
        @yield('content')
    </main>
</body>
</html>
