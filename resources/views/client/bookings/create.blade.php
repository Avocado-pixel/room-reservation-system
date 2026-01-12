<x-layouts.cliente>
    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="text-lg font-medium text-gray-900">Book: {{ $room->name }}</div>
                <div class="mt-1 text-sm text-gray-600">Pick a date and duration, then choose an available start time.</div>

                @if ($errors->any())
                    <div class="mt-4 rounded-md bg-red-50 p-4">
                        <div class="text-sm font-medium text-red-800">{{ $errors->first() }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 h-full">
                    <form method="POST" action="{{ route('client.bookings.store', $room) }}" class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                    @csrf

                    <div class="sm:col-span-3">
                        <label for="booking-date" class="block text-sm font-medium text-gray-700">Date</label>
                        <input id="booking-date" type="date" name="date" value="{{ old('date') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Duration (minutes)</label>
                        <select id="booking-duration" name="duration" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @php($dur = (int) old('duration', 60))
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

                        <input type="hidden" name="time" id="booking-time" value="{{ old('time') }}" required />

                        <div id="slots-loading" class="mt-3 hidden text-sm text-gray-600">Loading available times…</div>
                        <div id="slots-empty" class="mt-3 hidden rounded-md bg-yellow-50 p-4 text-sm text-yellow-800">
                            No available start times for this date and duration.
                        </div>
                        <div id="slots-error" class="mt-3 hidden rounded-md bg-red-50 p-4 text-sm text-red-800"></div>

                        <div id="slots-grid" data-endpoint="{{ route('client.bookings.availability', $room) }}" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6"></div>
                    </div>

                    <div class="sm:col-span-6 flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('client.rooms.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                            Create booking
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
                                // clear invalid selection
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

                            const url = `${endpoint}?date=${encodeURIComponent(date)}&duration=${encodeURIComponent(duration)}`;
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

                        // initial load (handles old() values)
                        loadSlots();
                    })();
                    </script>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4 h-full">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Recurring booking</div>
                        <div class="text-sm text-gray-600">Choose a weekly pattern, date range, duration (30-min steps), and a start slot (green).</div>
                    </div>
                    <form method="POST" action="{{ route('client.bookings.storeRecurring', $room) }}" class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                        @csrf
                        <input type="hidden" name="recurrence_type" value="custom_days" />
                        <input type="hidden" name="start_time" id="recurring-start-time" value="{{ old('start_time') }}" />
                        <input type="hidden" name="end_time" id="recurring-end-time" value="{{ old('end_time') }}" />

                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Start date</label>
                            <input type="date" name="start_date" id="recurring-start-date" min="{{ now()->format('Y-m-d') }}" value="{{ old('start_date') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">End date</label>
                            <input type="date" name="end_date" id="recurring-end-date" value="{{ old('end_date') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Duration (minutes)</label>
                            @php($recDur = (int) old('duration', 60))
                            <select name="duration" id="recurring-duration" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach([30,60,90,120] as $m)
                                    <option value="{{ $m }}" @selected($recDur === $m)>{{ $m }}</option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-xs text-gray-500">Slots are 30-minute aligned.</div>
                        </div>

                        <div class="sm:col-span-6">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="text-sm font-medium text-gray-700">Start slot</div>
                                    <div class="text-xs text-gray-500">Available slots (green). Disabled if they end after closing or are already past today.</div>
                                </div>
                                <div id="recurring-selected-slot" class="hidden rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700"></div>
                            </div>
                            <div id="recurring-slots-empty" class="mt-2 hidden rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">No valid start slots for the selected duration.</div>
                            <div id="recurring-slots-grid" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6"></div>
                        </div>

                        <div class="sm:col-span-6">
                            <label class="block text-sm font-medium text-gray-700">Days of week</label>
                            <div class="mt-2 grid grid-cols-3 gap-2 text-sm text-gray-700">
                                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $idx => $label)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="days_of_week[]" value="{{ $idx }}" class="rounded border-gray-300 text-indigo-600" />
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="sm:col-span-6 flex items-center justify-end gap-3 pt-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                                Create recurring
                            </button>
                        </div>
                    </form>

                    <script>
                    (function () {
                        const grid = document.getElementById('recurring-slots-grid');
                        const empty = document.getElementById('recurring-slots-empty');
                        const selectedBadge = document.getElementById('recurring-selected-slot');
                        const startInput = document.getElementById('recurring-start-time');
                        const endInput = document.getElementById('recurring-end-time');
                        const dateInput = document.getElementById('recurring-start-date');
                        const durationInput = document.getElementById('recurring-duration');

                        const base = 'w-full rounded-lg border px-3 py-2 text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
                        const available = 'bg-green-50 text-green-800 border-green-200 hover:bg-green-100 focus:ring-green-500';
                        const selected = 'bg-green-600 text-white border-green-600 hover:bg-green-700 focus:ring-green-500';
                        const disabled = 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed';

                        function minutesToHHMM(total) {
                            const h = Math.floor(total / 60);
                            const m = total % 60;
                            return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                        }

                        function regenerateSlots() {
                            const duration = parseInt(durationInput.value || '0', 10);
                            const today = new Date();
                            const selectedDate = dateInput.value ? new Date(dateInput.value + 'T00:00:00') : null;

                            grid.innerHTML = '';
                            selectedBadge.classList.add('hidden');
                            empty.classList.add('hidden');

                            if (!duration || !selectedDate) {
                                return;
                            }

                            const slots = [];
                            for (let h = 8; h <= 20; h++) {
                                for (const m of [0, 30]) {
                                    slots.push(h * 60 + m);
                                }
                            }

                            let usable = 0;
                            slots.forEach(totalMinutes => {
                                const startLabel = minutesToHHMM(totalMinutes);
                                const endTotal = totalMinutes + duration;
                                const endsAfterClose = endTotal > 20 * 60;

                                let isPastToday = false;
                                if (selectedDate) {
                                    const now = new Date();
                                    const isSameDay = now.getFullYear() === selectedDate.getFullYear() && now.getMonth() === selectedDate.getMonth() && now.getDate() === selectedDate.getDate();
                                    if (isSameDay) {
                                        const nowMinutes = now.getHours() * 60 + now.getMinutes();
                                        isPastToday = totalMinutes <= nowMinutes;
                                    }
                                }

                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.textContent = startLabel;
                                btn.dataset.time = startLabel;

                                const isDisabled = endsAfterClose || isPastToday;
                                if (isDisabled) {
                                    btn.className = `${base} ${disabled}`;
                                    btn.disabled = true;
                                } else {
                                    usable++;
                                    const isSelected = startInput.value === startLabel;
                                    btn.className = `${base} ${isSelected ? selected : available}`;
                                    btn.addEventListener('click', () => {
                                        startInput.value = startLabel;
                                        endInput.value = minutesToHHMM(endTotal);
                                        selectedBadge.textContent = `Selected: ${startLabel} → ${minutesToHHMM(endTotal)}`;
                                        selectedBadge.classList.remove('hidden');
                                        [...grid.querySelectorAll('button[data-time]')].forEach(b => {
                                            const on = b.dataset.time === startLabel;
                                            b.className = `${base} ${on ? selected : available}`;
                                        });
                                    });
                                }

                                grid.appendChild(btn);
                            });

                            if (usable === 0) {
                                empty.classList.remove('hidden');
                                startInput.value = '';
                                endInput.value = '';
                            }
                        }

                        dateInput.addEventListener('change', regenerateSlots);
                        durationInput.addEventListener('change', regenerateSlots);
                        regenerateSlots();
                    })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-layouts.cliente>