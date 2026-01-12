<x-layouts.public>
	<div class="py-12 bg-gray-50 min-h-screen">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

			<div class="flex flex-col gap-4 mb-8">
				<div>
					<h1 class="text-3xl font-bold text-gray-900 tracking-tight">Available Spaces</h1>
					<p class="mt-2 text-sm text-gray-600">Choose a room and click “Book Now” to reserve it.</p>
				</div>

				<form method="get" class="flex flex-wrap items-center gap-3 bg-white shadow-sm ring-1 ring-gray-200 rounded-2xl p-4 text-sm">
					<label class="text-sm font-medium text-gray-700">Sort</label>
					<select name="sort" class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
						<option value="name_asc" @selected(($sort ?? 'name_asc')==='name_asc')>Name A-Z</option>
						<option value="name_desc" @selected(($sort ?? '')==='name_desc')>Name Z-A</option>
						<option value="cap_asc" @selected(($sort ?? '')==='cap_asc')>Capacity ↑</option>
						<option value="cap_desc" @selected(($sort ?? '')==='cap_desc')>Capacity ↓</option>
					</select>

					<div class="relative flex-1 min-w-[220px] sm:min-w-[280px]">
						<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
							<svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
							</svg>
						</div>
						<input name="q" value="{{ $q }}" placeholder="Search by name or capacity..." class="block w-full pl-10 pr-3 py-2.5 rounded-md border border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
					</div>

					<button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Search</button>
				</form>
			</div>

			@if (session('status'))
				<div class="mb-6 rounded-xl bg-green-50 p-4 border border-green-100 flex items-center gap-3">
					<svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
					<span class="text-sm font-medium text-green-800">{{ session('status') }}</span>
				</div>
			@endif

			@if($rooms->isEmpty())
				<div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-gray-200">
					<svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
					<h3 class="mt-2 text-sm font-medium text-gray-900">No rooms found</h3>
					<p class="mt-1 text-sm text-gray-500">Try adjusting your search.</p>
				</div>
			@else
				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
					@foreach($rooms as $room)
						<div class="group bg-white rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col">

							<div class="relative h-56 w-full overflow-hidden">
								@if($room->photo)
									<img src="{{ asset('storage/'.$room->photo) }}" alt="{{ $room->name }}" class="h-full w-full object-cover transform group-hover:scale-110 transition-transform duration-500" />
								@else
									<div class="h-full w-full bg-gray-100 flex items-center justify-center text-gray-400 italic text-sm">
										No Image Available
									</div>
								@endif

								<div class="absolute top-4 right-4">
									<span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm {{ $room->status === 'available' ? 'bg-green-500 text-white' : 'bg-gray-800 text-white' }}">
										{{ str_replace('_', ' ', $room->status) }}
									</span>
								</div>
							</div>

							<div class="p-6 flex-grow">
								<div class="flex justify-between items-start mb-4">
									<h3 class="text-xl font-bold text-gray-900">{{ $room->name }}</h3>
									<div class="flex items-center text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg">
										<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
										<span class="text-xs font-bold">{{ $room->capacity }}</span>
									</div>
								</div>

								<p class="text-gray-600 text-sm leading-relaxed line-clamp-2">
									{{ $room->description ?? 'Experience our high-end room equipped with the latest technology for your comfort.' }}
								</p>
							</div>

							<div class="px-6 pb-6">
								@if($room->status === 'available')
									<a href="{{ route('client.bookings.create', $room) }}"
									   class="block w-full text-center bg-gray-900 text-white py-3 rounded-xl font-bold text-sm hover:bg-indigo-600 transition-colors shadow-sm">
										Book Now
									</a>
								@else
									<button disabled
										class="block w-full text-center bg-gray-100 text-gray-400 py-3 rounded-xl font-bold text-sm cursor-not-allowed">
										Currently Unavailable
									</button>
								@endif
							</div>
						</div>
					@endforeach
				</div>

				<div class="mt-12">
					{{ $rooms->links() }}
				</div>
			@endif
		</div>
	</div>
</x-layouts.public>
