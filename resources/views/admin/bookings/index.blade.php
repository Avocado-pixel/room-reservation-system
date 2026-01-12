<x-layouts.admin>
	<div class="space-y-6">
		<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
			<div class="p-6">
				<div class="text-lg font-medium text-gray-900">Bookings by day</div>
				<div class="mt-1 text-sm text-gray-600">Pick a day to view occupancy and filter by room.</div>
				<form method="get" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-4 sm:items-end">
					<div>
						<label class="block text-sm font-medium text-gray-700">Day</label>
						<input type="date" name="date" value="{{ $date }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
					</div>
					<div class="sm:col-span-2">
						<label class="block text-sm font-medium text-gray-700">Room</label>
						<input name="q" value="{{ $q }}" placeholder="Search by room..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
					</div>
					<div class="sm:col-span-1 flex justify-end">
						<button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
							View bookings
						</button>
					</div>
				</form>
			</div>
		</div>
		<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
			<div class="p-6">
				@if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
					<div class="text-sm text-gray-600">Select a day above and click “View bookings”.</div>
				@else
					@if($bookings->count() === 0)
						<div class="text-sm text-gray-600">There are no bookings for this day (or for the applied filter).</div>
					@else
						<div class="overflow-x-auto">
							<table class="min-w-full divide-y divide-gray-200">
								<thead class="bg-gray-50">
									<tr>
										<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
										<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
										<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
										<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start</th>
										<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End</th>
									</tr>
								</thead>
								<tbody class="bg-white divide-y divide-gray-200">
									@foreach($bookings as $b)
										<tr>
											<td class="px-4 py-3 text-sm text-gray-900">{{ $b->room?->name ?? '—' }}</td>
											<td class="px-4 py-3 text-sm text-gray-600">{{ $b->user?->name ?? '—' }}</td>
											<td class="px-4 py-3 text-sm text-gray-600">{{ $b->user?->email ?? '—' }}</td>
											<td class="px-4 py-3 text-sm text-gray-600">{{ $b->start_date->format('Y-m-d H:i') }}</td>
											<td class="px-4 py-3 text-sm text-gray-600">{{ $b->end_date->format('Y-m-d H:i') }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
						<div class="mt-6">
							{{ $bookings->links() }}
						</div>
					@endif
				@endif
			</div>
		</div>
	</div>
</x-layouts.admin>
