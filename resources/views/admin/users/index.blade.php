<x-layouts.admin>
	<div class="space-y-6">
		<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
			<div class="p-6 space-y-4">
				<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
					<div>
						<div class="text-lg font-medium text-gray-900">Users</div>
						<div class="mt-1 text-sm text-gray-600">Search and manage statuses/roles.</div>
					</div>
					<form method="get" class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
						<div class="flex flex-wrap items-center gap-3">
							<div class="flex flex-col gap-1">
								<label class="text-xs font-semibold text-gray-700">Role</label>
								<select name="role" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
									<option value="all" @selected($role==='all')>All</option>
									<option value="admin" @selected($role==='admin')>Admin</option>
									<option value="user" @selected($role==='user')>User</option>
								</select>
							</div>
							<div class="flex flex-col gap-1">
								<label class="text-xs font-semibold text-gray-700">Status</label>
								<select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
									<option value="all" @selected($status==='all')>All</option>
									<option value="pending" @selected($status==='pending')>Pending</option>
									<option value="active" @selected($status==='active')>Active</option>
									<option value="blocked" @selected($status==='blocked')>Blocked</option>
									<option value="deleted" @selected($status==='deleted')>Deleted</option>
								</select>
							</div>
							<div class="flex flex-col gap-1">
								<label class="text-xs font-semibold text-gray-700">Sort</label>
								<select name="sort" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
									<option value="name_asc" @selected(($sort ?? 'name_asc')==='name_asc')>Name A-Z</option>
									<option value="name_desc" @selected(($sort ?? '')==='name_desc')>Name Z-A</option>
								</select>
							</div>
						</div>
						<div class="flex flex-col sm:flex-row sm:items-center gap-2">
							<label class="sr-only">Search</label>
							<input name="q" value="{{ $q }}" placeholder="Search user..." class="w-full sm:w-72 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
							<button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
								Search
							</button>
						</div>
					</form>
				</div>
				@if (session('status'))
					<div class="rounded-md bg-green-50 p-4">
						<div class="text-sm font-medium text-green-800">{{ session('status') }}</div>
					</div>
				@endif
				@if ($errors->any())
					<div class="rounded-md bg-red-50 p-4">
						<div class="text-sm font-medium text-red-800">{{ $errors->first() }}</div>
					</div>
				@endif
			</div>
		</div>
		<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
			<div class="p-6 overflow-x-auto">
				<table class="min-w-full divide-y divide-gray-200">
					<thead class="bg-gray-50">
						<tr>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIF</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
							<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
						</tr>
					</thead>
					@foreach($users as $u)
						<tbody x-data="{ manageOpen: false }" class="bg-white divide-y divide-gray-200">
							<tr>
								<td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $u->name }}</td>
								<td class="px-4 py-3 text-sm text-gray-600">{{ $u->email }}</td>
								<td class="px-4 py-3 text-sm text-gray-600">{{ $u->tax_id ?? '' }}</td>
								<td class="px-4 py-3 text-sm text-gray-600">
									@if($u->phone)
										@php
											$phone = $u->phone;
											$country = strtoupper($u->phone_country ?? '');
											$dialCodes = [
												'ES' => '+34', 'PT' => '+351', 'FR' => '+33', 'DE' => '+49',
												'GB' => '+44', 'IT' => '+39', 'US' => '+1', 'BR' => '+55',
												'MX' => '+52', 'AR' => '+54', 'CO' => '+57', 'NL' => '+31',
												'BE' => '+32', 'CH' => '+41', 'AT' => '+43', 'PL' => '+48',
											];
											$dialCode = $dialCodes[$country] ?? '';
											// Remove + from phone if present to avoid duplicating
											$nationalNumber = ltrim($phone, '+');
											// If phone already contains dial code, remove it
											if ($dialCode && str_starts_with($nationalNumber, ltrim($dialCode, '+'))) {
												$nationalNumber = substr($nationalNumber, strlen(ltrim($dialCode, '+')));
											}
										@endphp
										<span class="inline-flex items-center gap-1.5">
											@if($country)
												<span class="text-gray-400 font-mono text-xs">{{ $country }}</span>
											@endif
											<span>{{ $dialCode }}{{ $nationalNumber }}</span>
										</span>
									@endif
								</td>
								<td class="px-4 py-3 text-sm text-gray-600">{{ $u->role ?? 'user' }}</td>
								<td class="px-4 py-3 text-sm text-gray-600">{{ $u->status ?? 'pending' }}</td>
								<td class="px-4 py-3">
									<div class="flex flex-wrap items-center justify-end gap-2">
										<a class="text-sm font-medium text-indigo-600 hover:text-indigo-500" href="{{ route('admin.users.index', array_filter(['q' => $q, 'view' => $u->id])) }}">View bookings</a>
										<button type="button" @click="manageOpen = !manageOpen" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
											Manage
										</button>
									</div>
								</td>
							</tr>
							<tr x-show="manageOpen" x-cloak>
								<td colspan="7" class="px-4 py-4 bg-gray-50">
									<div class="rounded-lg border border-gray-200 bg-white p-4">
										<div class="flex items-start justify-between gap-4">
											<div>
												<div class="text-sm font-medium text-gray-900">Manage user</div>
												<div class="mt-1 text-sm text-gray-600">Status and role (safely).</div>
											</div>
											<button type="button" @click="manageOpen = false" class="text-sm font-medium text-gray-600 hover:text-gray-900">Close</button>
										</div>
										<div class="mt-4 flex flex-wrap gap-2">
											@if(($u->role ?? 'user') !== 'admin')
												<form method="post" action="{{ route('admin.users.status', ['user' => $u->id]) }}">
													@csrf
													<input type="hidden" name="status" value="{{ ($u->status ?? 'pending') === 'blocked' ? 'active' : 'blocked' }}" />
													@if(($u->status ?? 'pending') === 'blocked')
														<button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
															Unblock
														</button>
													@else
														<button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition">
															Block
														</button>
													@endif
												</form>
											@endif
											@if(auth()->id() !== $u->id)
												<form method="post" action="{{ route('admin.users.role', ['user' => $u->id]) }}">
													@csrf
													<input type="hidden" name="role" value="{{ ($u->role ?? 'user') === 'admin' ? 'user' : 'admin' }}" />
													<button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
														{{ ($u->role ?? 'user') === 'admin' ? 'Make user' : 'Make admin' }}
													</button>
												</form>
											@endif
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					@endforeach
				</table>
			</div>
			<div class="p-6 pt-0">
				{{ $users->links() }}
			</div>
		</div>

		@if(!is_null($bookings))
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6">
					<div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
						<div>
							<div class="text-lg font-medium text-gray-900">Bookings</div>
							<div class="mt-1 text-sm text-gray-600">
								@if($viewUser)
									Showing bookings for <span class="font-medium">{{ $viewUser->name }}</span> ({{ $viewUser->email }}).
								@else
									User not found.
								@endif
							</div>
						</div>
						<a class="text-sm font-medium text-gray-600 hover:text-gray-900" href="{{ route('admin.users.index', array_filter(['q' => $q])) }}">Clear</a>
					</div>

					@if($viewUser && $bookings->count() === 0)
						<div class="mt-4 text-sm text-gray-600">This user has no bookings.</div>
					@elseif($viewUser)
						<div class="mt-4 overflow-x-auto">
							<table class="min-w-full divide-y divide-gray-200">
								<thead class="bg-gray-50">
									<tr>
										<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
										<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start</th>
										<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End</th>
									</tr>
								</thead>
								<tbody class="bg-white divide-y divide-gray-200">
									@foreach($bookings as $b)
										<tr>
											<td class="px-4 py-3 text-sm text-gray-900">{{ $b->room?->name ?? '—' }}</td>
											<td class="px-4 py-3 text-sm text-gray-600">{{ optional($b->start_date)->format('Y-m-d H:i') }}</td>
											<td class="px-4 py-3 text-sm text-gray-600">{{ optional($b->end_date)->format('Y-m-d H:i') }}</td>
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
		@endif
	</div>
</x-layouts.admin>
