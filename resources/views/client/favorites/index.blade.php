<x-layouts.cliente>
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8 gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Favorite Rooms</h1>
                    <p class="mt-2 text-sm text-gray-600">Quick access to rooms you bookmarked.</p>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-xl bg-green-50 p-4 border border-green-100 flex items-center gap-3">
                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span class="text-sm font-medium text-green-800">{{ session('status') }}</span>
                </div>
            @endif

            @if($rooms->isEmpty())
                <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-gray-200">
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No favorites yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Browse rooms and tap “Add to favorites”.</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($rooms as $room)
                        <div class="group bg-white rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col">
                            <div class="relative h-48 w-full overflow-hidden">
                                @if($room->photo)
                                    <img src="{{ asset('storage/'.$room->photo) }}" alt="{{ $room->name }}" class="h-full w-full object-cover" />
                                @else
                                    <div class="h-full w-full bg-gray-100 flex items-center justify-center text-gray-400 italic text-sm">No Image</div>
                                @endif
                            </div>

                            <div class="p-6 flex-1 flex flex-col gap-3">
                                <div class="flex items-start justify-between">
                                    <h3 class="text-xl font-bold text-gray-900">{{ $room->name }}</h3>
                                    <span class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 font-semibold">Cap: {{ $room->capacity }}</span>
                                </div>
                                <p class="text-sm text-gray-600 line-clamp-2">{{ $room->description ?? 'No description available.' }}</p>
                            </div>

                            <div class="px-6 pb-6 flex gap-3">
                                <form method="post" action="{{ route('client.favorites.destroy', $room) }}" class="w-1/2">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="w-full text-center border border-gray-200 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-50 transition">Remove</button>
                                </form>
                                @if($room->status === 'available')
                                    <a href="{{ route('client.bookings.create', $room) }}" class="w-1/2 text-center bg-gray-900 text-white py-3 rounded-xl font-semibold hover:bg-indigo-600 transition">Book</a>
                                @else
                                    <span class="w-1/2 text-center bg-gray-100 text-gray-400 py-3 rounded-xl font-semibold">Unavailable</span>
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
</x-layouts.cliente>
