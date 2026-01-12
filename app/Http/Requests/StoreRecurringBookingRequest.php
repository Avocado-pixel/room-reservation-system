<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isUser();
    }

    protected function prepareForValidation(): void
    {
        $days = $this->input('days_of_week');
        if (is_string($days)) {
            $decoded = json_decode($days, true);
            if (is_array($decoded)) {
                $this->merge(['days_of_week' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'recurrence_type' => ['required', 'in:weekly,custom_days'],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'between:0,6'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i', Rule::in($this->allowedTimes())],
            'duration' => ['required', 'integer', 'in:30,60,90,120'],
            // end_time is computed server-side from start_time + duration to avoid tampering.
            'end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * Allowed time slots in 30-minute steps (08:00–20:00).
     *
     * @return array<int, string>
     */
    private function allowedTimes(): array
    {
        $slots = [];
        for ($h = 8; $h <= 20; $h++) {
            foreach ([0, 30] as $m) {
                $slots[] = sprintf('%02d:%02d', $h, $m);
            }
        }
        return $slots;
    }
}
