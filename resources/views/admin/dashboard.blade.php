<x-layouts.admin>
    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="text-lg font-medium text-gray-900">Management</div>
                <div class="mt-1 text-sm text-gray-600">Rooms, bookings, and users.</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <a href="{{ route('admin.rooms.index') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow transition">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-500">Rooms</div>
                    <div class="mt-2 text-xl font-semibold text-gray-900">Manage rooms</div>
                </div>
            </a>

            <a href="{{ route('admin.bookings.index') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow transition">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-500">Bookings</div>
                    <div class="mt-2 text-xl font-semibold text-gray-900">View bookings</div>
                </div>
            </a>

            <a href="{{ route('admin.users.index') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow transition">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-500">Users</div>
                    <div class="mt-2 text-xl font-semibold text-gray-900">Manage accounts</div>
                </div>
            </a>
        </div>
    </div>
</x-layouts.admin>
