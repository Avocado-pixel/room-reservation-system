<x-layouts.public>
    <div class="max-w-xl mx-auto space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="text-lg font-medium text-gray-900">Validate account</div>

                @if (session('status'))
                    <div class="mt-4 rounded-md bg-green-50 p-4">
                        <div class="text-sm font-medium text-green-800">{{ session('status') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-4 rounded-md bg-red-50 p-4">
                        <div class="text-sm font-medium text-red-800">{{ $errors->first() }}</div>
                    </div>
                @endif

                <div class="mt-4 text-sm text-gray-600">
                    Enter the email you used to register and the 6-digit code you received by email.
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 space-y-6">
                <form method="post" action="{{ route('validate-account.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email', $email) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Validation code (6 digits)</label>
                        <input type="text" name="code" maxlength="6" pattern="\d{6}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                            Validate account
                        </button>
                    </div>
                </form>

                <div class="border-t border-gray-200"></div>

                <form method="post" action="{{ route('validate-account.resend') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email', $email) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            Resend code
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.public>
