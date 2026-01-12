<x-guest-layout>
	<div class="min-h-screen bg-gray-50">
		<nav class="bg-white border-b border-gray-100">
			<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
				<div class="flex justify-between h-16">
					<div class="flex items-center">
						<a href="{{ route('rooms.public.index') }}" class="text-sm font-semibold text-gray-900">
							{{ config('app.name', 'SAW') }}
						</a>
					</div>

					<div class="flex items-center gap-3">
						@auth
							<a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">Dashboard</a>
						@else
							<a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">Login</a>
							<a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
								Register
							</a>
						@endauth
					</div>
				</div>
			</div>
		</nav>

		<main class="py-10">
			<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
				{{ $slot }}
			</div>
		</main>
	</div>
</x-guest-layout>
