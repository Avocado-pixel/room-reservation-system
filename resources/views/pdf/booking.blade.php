<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking #{{ $booking->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111; }
        h1 { font-size: 20px; margin-bottom: 8px; }
        h2 { font-size: 16px; margin-top: 18px; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px 4px; font-size: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .muted { color: #555; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Room Booking Confirmation</h1>
    <p class="muted">Generated at {{ now()->format('Y-m-d H:i') }} ({{ config('app.timezone') }})</p>

    <h2>Booking Details</h2>
    <table>
        <tr><th>ID</th><td>{{ $booking->id }}</td></tr>
        <tr><th>Room</th><td>{{ $room->name }}</td></tr>
        <tr><th>User</th><td>{{ $user->name }} ({{ $user->email }})</td></tr>
        <tr><th>Starts</th><td>{{ $start->format('Y-m-d H:i') }}</td></tr>
        <tr><th>Ends</th><td>{{ $end->format('Y-m-d H:i') }}</td></tr>
        @if($room->capacity)
            <tr><th>Capacity</th><td>{{ $room->capacity }}</td></tr>
        @endif
        @if($room->description)
            <tr><th>Description</th><td>{{ $room->description }}</td></tr>
        @endif
        @if($room->equipment)
            <tr><th>Equipment</th><td>{{ implode(', ', $room->equipment ?? []) }}</td></tr>
        @endif
        @if($room->usage_rules)
            <tr><th>Usage rules</th><td>{{ $room->usage_rules }}</td></tr>
        @endif
    </table>

    @if($booking->recurringReservation)
        <h2>Recurrence</h2>
        <table>
            <tr><th>Type</th><td>{{ $booking->recurringReservation->recurrence_type }}</td></tr>
            <tr><th>Days</th><td>{{ implode(', ', $booking->recurringReservation->days_of_week ?? []) }}</td></tr>
            <tr><th>Range</th><td>{{ $booking->recurringReservation->start_date }} to {{ $booking->recurringReservation->end_date }}</td></tr>
        </table>
    @endif
</body>
</html>
