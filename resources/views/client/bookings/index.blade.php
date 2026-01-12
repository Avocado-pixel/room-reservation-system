<x-layouts.cliente>
	<div class="space-y-6">
		<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
			<div class="p-6">
				<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
					<div>
						<div class="text-lg font-medium text-gray-900">My bookings</div>
						<div class="mt-1 text-sm text-gray-600">Your upcoming and past reservations.</div>
					</div>
					<a href="{{ route('client.rooms.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
						Book a room
					</a>
				</div>

				@if (session('status'))
					<div class="mt-4 rounded-md bg-green-50 p-4">
						<div class="text-sm font-medium text-green-800">{{ session('status') }}</div>
					</div>
				@endif
			</div>
		</div>

		<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
			<div class="p-6">
				<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
					<div class="text-sm text-gray-600">Filter your bookings.</div>
					<form method="get" class="flex items-center gap-3">
						<label class="text-sm font-medium text-gray-700">Show</label>
						<select name="filter" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
							<option value="all" @selected(($filter ?? 'all')==='all')>All</option>
							<option value="future" @selected(($filter ?? '')==='future')>Future</option>
							<option value="past" @selected(($filter ?? '')==='past')>Past</option>
						</select>
					</form>
				</div>

				@if($bookings->count() === 0)
					<div class="text-sm text-gray-600">You don’t have any bookings yet.</div>
				@else
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
									<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start</th>
									<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End</th>
									<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								@foreach($bookings as $b)
									@php($isFuture = optional($b->start_date)->isFuture())
									<tr>
										<td class="px-4 py-3 text-sm text-gray-900">{{ $b->room?->name ?? '—' }}</td>
										<td class="px-4 py-3 text-sm text-gray-600">{{ optional($b->start_date)->format('Y-m-d H:i') }}</td>
										<td class="px-4 py-3 text-sm text-gray-600">{{ optional($b->end_date)->format('Y-m-d H:i') }}</td>
										<td class="px-4 py-3 text-sm text-right">
											<div class="flex flex-wrap items-center justify-end gap-2">
												<a href="{{ route('client.bookings.export.ics', $b) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100">ICS</a>
												<a href="{{ route('client.bookings.export.gcal', $b) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100">Google</a>
												<a href="{{ route('client.bookings.export.pdf', $b) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100">PDF</a>
												@if($isFuture)
													<a href="{{ route('client.bookings.edit', $b) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold rounded-lg bg-white border border-gray-200 text-gray-800 shadow-sm hover:border-indigo-200 hover:text-indigo-700">Edit</a>
													<form method="POST" action="{{ route('client.bookings.destroy', $b) }}" onsubmit="return confirm('Cancel this booking?');" class="inline-flex">
														@csrf
														@method('DELETE')
														<button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm font-semibold rounded-lg bg-red-50 border border-red-100 text-red-700 hover:bg-red-100">Cancel</button>
													</form>
												@else
													<form method="POST" action="{{ route('client.feedback.store', $b->room) }}" class="flex items-center gap-2">
														@csrf
														<select name="rating" class="text-xs rounded border-gray-300">
															@for($i=1;$i<=5;$i++)
																<option value="{{ $i }}">{{ $i }}★</option>
															@endfor
														</select>
														<input name="comment" placeholder="Comment" class="text-xs rounded border-gray-300" />
														<button type="submit" class="text-xs font-semibold text-gray-700 hover:text-gray-900">Rate</button>
													</form>
												@endif
											</div>
										</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
					<div class="mt-6">
						{{ $bookings->links() }}
					</div>
				@endif
			</div>
		</div>
	</div>
</x-layouts.cliente>
