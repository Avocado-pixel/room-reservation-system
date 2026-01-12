<x-layouts.admin>
	<div class="space-y-6" x-data="{ addOpen: false }">
		<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
			<div class="p-6 space-y-4">
				<div class="flex flex-col gap-3">
					<h1 class="text-3xl font-bold text-gray-900">Rooms</h1>
					<form method="get" class="flex flex-wrap items-center justify-between gap-3">
						<div class="flex flex-wrap items-center gap-3">
							<div class="flex flex-col gap-1">
								<label class="text-xs font-semibold text-gray-700">Status</label>
								<select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
									<option value="all" @selected($status==='all')>All</option>
									<option value="available" @selected($status==='available')>Available</option>
									<option value="unavailable" @selected($status==='unavailable')>Unavailable</option>
									<option value="coming_soon" @selected($status==='coming_soon')>Coming soon</option>
								</select>
							</div>
							<div class="flex flex-col gap-1">
								<label class="text-xs font-semibold text-gray-700">Sort</label>
								<select name="sort" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
									<option value="name_asc" @selected(($sort ?? 'name_asc')==='name_asc')>Name A-Z</option>
									<option value="name_desc" @selected(($sort ?? '')==='name_desc')>Name Z-A</option>
									<option value="cap_asc" @selected(($sort ?? '')==='cap_asc')>Capacity ↑</option>
									<option value="cap_desc" @selected(($sort ?? '')==='cap_desc')>Capacity ↓</option>
								</select>
							</div>
						</div>
						<div class="flex items-center gap-2">
							<label class="sr-only">Search</label>
							<div class="relative w-72">
								<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
									<svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 0 0114 0z" />
									</svg>
								</div>
								<input name="q" value="{{ $q }}" placeholder="Search rooms..." class="block w-full pl-10 pr-3 py-2.5 rounded-md border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
							</div>
							<button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
								Search
							</button>
						</div>
					</form>
					<div class="flex justify-end">
						<button type="button" @click="addOpen = !addOpen" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-semibold rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
							<svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
							</svg>
							Add Room
						</button>
					</div>
				</div>

				<div x-show="addOpen" x-cloak class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
						<div class="flex items-start justify-between mb-4">
							<h3 class="text-lg font-semibold text-gray-900">Create room</h3>
							<button type="button" @click="addOpen = false" class="text-sm font-medium text-gray-600 hover:text-gray-900">Close</button>
						</div>
						<form method="POST" action="{{ route('admin.rooms.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 sm:grid-cols-6 sm:items-end">
							@csrf
							<div class="sm:col-span-3">
								<label class="block text-sm font-medium text-gray-700">Name</label>
								<input name="name" placeholder="e.g. Room Alpha" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
							</div>
							<div class="sm:col-span-1">
								<label class="block text-sm font-medium text-gray-700">Capacity</label>
								<input name="capacity" type="number" value="10" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
							</div>
							<div class="sm:col-span-2">
								<label class="block text-sm font-medium text-gray-700">Status</label>
								<select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
									<option value="available">Available</option>
									<option value="unavailable">Unavailable</option>
									<option value="coming_soon">Coming soon</option>
								</select>
							</div>
							<div class="sm:col-span-6">
								<label class="block text-sm font-medium text-gray-700">Description</label>
								<textarea name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
							</div>
							<div class="sm:col-span-6">
								<label class="block text-sm font-medium text-gray-700">Equipment (comma or line separated)</label>
								<textarea name="equipment" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
							</div>
							<div class="sm:col-span-6">
								<label class="block text-sm font-medium text-gray-700">Usage rules</label>
								<textarea name="usage_rules" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
							</div>
							<div class="sm:col-span-4">
								<label class="block text-sm font-medium text-gray-700">Photo</label>
								<input type="file" name="photo" accept="image/*" class="mt-1 block w-full text-sm text-gray-700" />
							</div>
							<div class="sm:col-span-2 flex items-center justify-end gap-3">
								<button type="button" @click="addOpen = false" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</button>
								<button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
									Save
								</button>
							</div>
						</form>
					</div>
				</div>
		</div>
		<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
			<div class="p-6 overflow-x-auto">
				<table class="min-w-full divide-y divide-gray-200">
					<thead class="bg-gray-50">
						<tr>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
						</tr>
					</thead>
					@foreach($rooms as $room)
						<tbody x-data="{ editOpen: false }" class="bg-white divide-y divide-gray-200">
							<tr>
								<td class="px-4 py-3">
									@if($room->photo)
										<img src="{{ asset('storage/'.$room->photo) }}" alt="Photo" class="h-14 w-24 rounded-md object-cover" />
									@else
										<div class="text-sm text-gray-400">—</div>
									@endif
								</td>
								<td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $room->name }}</td>
								<td class="px-4 py-3 text-sm text-gray-600">{{ $room->capacity }}</td>
								<td class="px-4 py-3 text-sm text-gray-600">{{ $room->status }}</td>
								<td class="px-4 py-3">
									<div class="flex flex-wrap items-center justify-end gap-2">
										<button type="button" @click="editOpen = !editOpen" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
											Edit
										</button>
										<form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" onsubmit="return confirm('Are you sure you want to delete this room?');">
											@csrf
											@method('DELETE')
											<button type="submit" class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition">
												Delete
											</button>
										</form>
									</div>
								</td>
							</tr>
							<tr x-show="editOpen" x-cloak>
								<td colspan="5" class="px-4 py-4 bg-gray-50">
									<form method="POST" action="{{ route('admin.rooms.update', $room) }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 sm:grid-cols-6 sm:items-end">
										@csrf
										@method('PUT')
										<div class="sm:col-span-3">
											<label class="block text-sm font-medium text-gray-700">Name</label>
											<input name="name" value="{{ $room->name }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
										</div>
										<div class="sm:col-span-1">
											<label class="block text-sm font-medium text-gray-700">Capacity</label>
											<input name="capacity" type="number" value="{{ $room->capacity }}" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
										</div>
										<div class="sm:col-span-2">
											<label class="block text-sm font-medium text-gray-700">Status</label>
											<select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
												<option value="available" @if($room->status=='available') selected @endif>Available</option>
												<option value="unavailable" @if($room->status=='unavailable') selected @endif>Unavailable</option>
												<option value="coming_soon" @if($room->status=='coming_soon') selected @endif>Coming soon</option>
											</select>
										</div>
										<div class="sm:col-span-6">
											<label class="block text-sm font-medium text-gray-700">Description</label>
											<textarea name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $room->description }}</textarea>
										</div>
										<div class="sm:col-span-6">
											<label class="block text-sm font-medium text-gray-700">Equipment (comma or line separated)</label>
											<textarea name="equipment" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $room->equipment ? implode(', ', $room->equipment) : '' }}</textarea>
										</div>
										<div class="sm:col-span-6">
											<label class="block text-sm font-medium text-gray-700">Usage rules</label>
											<textarea name="usage_rules" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $room->usage_rules }}</textarea>
										</div>
										<div class="sm:col-span-4">
											<label class="block text-sm font-medium text-gray-700">Photo</label>
											<input type="file" name="photo" accept="image/*" class="mt-1 block w-full text-sm text-gray-700" />
										</div>
										<div class="sm:col-span-2 flex items-center justify-end gap-3">
											<button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
												Save
											</button>
											<button type="button" @click="editOpen = false" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</button>
										</div>
									</form>

									<div class="mt-6 rounded-lg border border-gray-200 bg-white p-4">
										<div class="flex items-center justify-between mb-3">
											<h4 class="text-sm font-semibold text-gray-800">Cancellation policies</h4>
											<small class="text-xs text-gray-500">Latest active policy applies.</small>
										</div>
										@if($room->cancellationPolicies->isEmpty())
											<p class="text-xs text-gray-500">No policies yet.</p>
										@else
											<ul class="space-y-2 text-sm">
												@foreach($room->cancellationPolicies as $policy)
													<li class="flex items-center justify-between gap-3 p-2 rounded border {{ $policy->is_active ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200' }}">
														<div>
															<div class="font-semibold text-gray-800">{{ $policy->name }} @if($policy->is_active)<span class="text-xs text-indigo-600">(active)</span>@endif</div>
															<div class="text-xs text-gray-500">Cancel before {{ $policy->cancel_before_hours }}h | Penalty: {{ $policy->penalty_type }} {{ $policy->penalty_value }}</div>
														</div>
														<div class="flex items-center gap-2">
															<form method="POST" action="{{ route('admin.cancellation-policies.update', $policy) }}" class="flex items-center gap-1">
																@csrf
																@method('put')
																<input type="hidden" name="name" value="{{ $policy->name }}" />
																<input type="hidden" name="cancel_before_hours" value="{{ $policy->cancel_before_hours }}" />
																<input type="hidden" name="penalty_type" value="{{ $policy->penalty_type }}" />
																<input type="hidden" name="penalty_value" value="{{ $policy->penalty_value }}" />
																<input type="hidden" name="is_active" value="1" />
																<button class="text-xs text-indigo-700 hover:text-indigo-900">Make active</button>
															</form>
															<form method="POST" action="{{ route('admin.cancellation-policies.destroy', $policy) }}" onsubmit="return confirm('Delete this policy?');">
																@csrf
																@method('delete')
																<button class="text-xs text-red-600 hover:text-red-700">Delete</button>
															</form>
														</div>
													</li>
												@endforeach
											</ul>
										@endif

										<form method="POST" action="{{ route('admin.cancellation-policies.store', $room) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-6">
											@csrf
											<div class="sm:col-span-2">
												<label class="block text-xs font-medium text-gray-700">Name</label>
												<input name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
											</div>
											<div class="sm:col-span-1">
												<label class="block text-xs font-medium text-gray-700">Hours before</label>
												<input type="number" name="cancel_before_hours" value="24" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
											</div>
											<div class="sm:col-span-1">
												<label class="block text-xs font-medium text-gray-700">Penalty</label>
												<select name="penalty_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
													<option value="none">None</option>
													<option value="percent">Percent</option>
													<option value="flat">Flat</option>
												</select>
											</div>
											<div class="sm:col-span-1">
												<label class="block text-xs font-medium text-gray-700">Value</label>
												<input type="number" step="0.01" min="0" name="penalty_value" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
											</div>
											<div class="sm:col-span-1 flex items-center gap-2 pt-5">
												<label class="text-xs text-gray-700"><input type="checkbox" name="is_active" value="1" class="mr-1">Active</label>
												<button type="submit" class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md shadow-sm hover:bg-indigo-700">Add</button>
											</div>
										</form>
									</div>
								</td>
							</tr>
						</tbody>
					@endforeach
				</table>
			</div>
			<div class="p-6 pt-0">
				{{ $rooms->links() }}
			</div>
		</div>
	</div>
</x-layouts.admin>
