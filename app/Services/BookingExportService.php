<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BookingExportService
{
    /**
     * Generate an ICS file content for the given booking.
     */
    public function generateIcs(Booking $booking): string
    {
        $booking->loadMissing('room');

        $start = $booking->start_date?->copy()->setTimezone('UTC');
        $end = $booking->end_date?->copy()->setTimezone('UTC');

        if (!$start || !$end) {
            throw new \InvalidArgumentException('Booking missing date information.');
        }

        $uid = sprintf('booking-%d@%s', $booking->id, parse_url(config('app.url'), PHP_URL_HOST) ?? 'app');
        $summary = addslashes($booking->room->name ?? 'Room Booking');
        $location = addslashes($booking->room->name ?? '');
        $description = 'Room booking';

        return "BEGIN:VCALENDAR\n" .
            "VERSION:2.0\n" .
            "PRODID:-//SAW//RoomBooking//EN\n" .
            "BEGIN:VEVENT\n" .
            "UID:$uid\n" .
            "DTSTAMP:" . $start->format('Ymd\THis\Z') . "\n" .
            "DTSTART:" . $start->format('Ymd\THis\Z') . "\n" .
            "DTEND:" . $end->format('Ymd\THis\Z') . "\n" .
            "SUMMARY:$summary\n" .
            "LOCATION:$location\n" .
            "DESCRIPTION:$description\n" .
            "END:VEVENT\n" .
            "END:VCALENDAR";
    }

    /**
     * Build a Google Calendar URL for the given booking.
     */
    public function buildGoogleCalendarUrl(Booking $booking): string
    {
        $booking->loadMissing('room');

        $start = $booking->start_date?->copy()->setTimezone('UTC');
        $end = $booking->end_date?->copy()->setTimezone('UTC');

        if (!$start || !$end) {
            throw new \InvalidArgumentException('Booking missing date information.');
        }

        $title = rawurlencode($booking->room->name ?? 'Room Booking');
        $details = rawurlencode('Room booking');
        $location = rawurlencode($booking->room->name ?? '');
        $dates = $start->format('Ymd\THis\Z') . '/' . $end->format('Ymd\THis\Z');

        return "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&details={$details}&location={$location}&dates={$dates}";
    }

    /**
     * Generate a PDF for the given booking.
     */
    public function generatePdf(Booking $booking): string
    {
        $booking->loadMissing(['room', 'user']);

        $start = $booking->start_date;
        $end = $booking->end_date;

        if (!$start || !$end) {
            throw new \InvalidArgumentException('Booking missing date information.');
        }

        $html = view('pdf.booking', [
            'booking' => $booking,
            'room' => $booking->room,
            'user' => $booking->user,
            'start' => $start,
            'end' => $end,
        ])->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
