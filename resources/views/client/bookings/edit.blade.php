<x-layouts.cliente>
    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="text-lg font-medium text-gray-900">Edit booking: {{ $room->name }}</div>
                <div class="mt-1 text-sm text-gray-600">Pick a new date and duration, then choose an available start time.</div>

                @if ($errors->any())
                    <div class="mt-4 rounded-md bg-red-50 p-4">
                        <div class="text-sm font-medium text-red-800">{{ $errors->first() }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('client.bookings.update', $booking) }}" class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                    @csrf
                    @method('PUT')

                    <div class="sm:col-span-3">
                        <label for="booking-date" class="block text-sm font-medium text-gray-700">Date</label>
                        <input id="booking-date" type="date" name="date" value="{{ old('date', $initialDate) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Duration (minutes)</label>
                        <select id="booking-duration" name="duration" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @php($dur = (int) old('duration', $initialDuration))
                            @foreach([30,60,90,120] as $m)
                                <option value="{{ $m }}" @selected($dur === $m)>{{ $m }}</option>
                            @endforeach
                        </select>
                        <div class="mt-1 text-xs text-gray-500">Durations are in 30-minute blocks.</div>
                    </div>

                    <div class="sm:col-span-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-700">Start time</div>
                                <div class="text-xs text-gray-500">Select one of the available slots (green).</div>
                            </div>
                            <div id="selected-slot" class="hidden rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700"></div>
                        </div>

                        <input type="hidden" name="time" id="booking-time" value="{{ old('time', $initialTime) }}" required />

                        <div id="slots-loading" class="mt-3 hidden text-sm text-gray-600">Loading available times…</div>
                        <div id="slots-empty" class="mt-3 hidden rounded-md bg-yellow-50 p-4 text-sm text-yellow-800">
                            No available start times for this date and duration.
                        </div>
                        <div id="slots-error" class="mt-3 hidden rounded-md bg-red-50 p-4 text-sm text-red-800"></div>

                        <div id="slots-grid" data-endpoint="{{ route('client.bookings.availability', $room) }}" data-exclude-booking-id="{{ $booking->id }}" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6"></div>
                    </div>

                    <div class="sm:col-span-6 flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('client.bookings.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                            Update booking
                        </button>
                    </div>
                </form>

                <script>
                    (function () {
                        const dateInput = document.getElementById('booking-date');
                        const durationInput = document.getElementById('booking-duration');
                        const timeInput = document.getElementById('booking-time');
                        const selectedSlot = document.getElementById('selected-slot');
                        const grid = document.getElementById('slots-grid');
                        const endpoint = grid.dataset.endpoint;
                        const excludeBookingId = grid.dataset.excludeBookingId;
                        const loading = document.getElementById('slots-loading');
                        const empty = document.getElementById('slots-empty');
                        const error = document.getElementById('slots-error');

                        let selectedTime = timeInput.value || '';

                        function setState({ isLoading, isEmpty, errorText }) {
                            loading.classList.toggle('hidden', !isLoading);
                            empty.classList.toggle('hidden', !isEmpty);
                            error.classList.toggle('hidden', !errorText);
                            error.textContent = errorText || '';
                        }

                        function renderSlots(times) {
                            grid.innerHTML = '';

                            if (!Array.isArray(times) || times.length === 0) {
                                setState({ isLoading: false, isEmpty: true, errorText: '' });
                                selectedSlot.classList.add('hidden');
                                return;
                            }

                            setState({ isLoading: false, isEmpty: false, errorText: '' });

                            const base = 'w-full rounded-lg border px-3 py-2 text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
                            const available = 'bg-green-50 text-green-800 border-green-200 hover:bg-green-100 focus:ring-green-500';
                            const selected = 'bg-green-600 text-white border-green-600 hover:bg-green-700 focus:ring-green-500';

                            times.forEach((t) => {
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.textContent = t;
                                btn.dataset.time = t;

                                const isSelected = selectedTime === t;
                                btn.className = `${base} ${isSelected ? selected : available}`;

                                btn.addEventListener('click', () => {
                                    selectedTime = t;
                                    timeInput.value = t;
                                    selectedSlot.textContent = `Selected: ${t}`;
                                    selectedSlot.classList.remove('hidden');
                                    [...grid.querySelectorAll('button[data-time]')].forEach((b) => {
                                        const on = b.dataset.time === t;
                                        b.className = `${base} ${on ? selected : available}`;
                                    });
                                });

                                grid.appendChild(btn);
                            });

                            if (selectedTime && times.includes(selectedTime)) {
                                selectedSlot.textContent = `Selected: ${selectedTime}`;
                                selectedSlot.classList.remove('hidden');
                            } else {
                                selectedTime = '';
                                timeInput.value = '';
                                selectedSlot.classList.add('hidden');
                            }
                        }

                        async function loadSlots() {
                            const date = (dateInput.value || '').trim();
                            const duration = (durationInput.value || '').trim();

                            grid.innerHTML = '';
                            setState({ isLoading: false, isEmpty: false, errorText: '' });

                            if (!date || !duration) {
                                return;
                            }

                            setState({ isLoading: true, isEmpty: false, errorText: '' });

                            const url = `${endpoint}?date=${encodeURIComponent(date)}&duration=${encodeURIComponent(duration)}&exclude_booking_id=${encodeURIComponent(excludeBookingId || '')}`;
                            try {
                                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                                const data = await res.json().catch(() => null);
                                if (!res.ok) {
                                    const msg = (data && (data.message || data.error)) ? (data.message || data.error) : 'Could not load availability.';
                                    setState({ isLoading: false, isEmpty: false, errorText: msg });
                                    return;
                                }
                                renderSlots((data && data.slots) ? data.slots : []);
                            } catch (e) {
                                setState({ isLoading: false, isEmpty: false, errorText: 'Network error loading availability.' });
                            }
                        }

                        dateInput.addEventListener('change', loadSlots);
                        durationInput.addEventListener('change', loadSlots);

                        loadSlots();
                    })();
                </script>
            </div>
        </div>
    </div>
</x-layouts.cliente>
