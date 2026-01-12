<x-guest-layout>
    <div class="bg-gray-50 py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex justify-center mb-5">
                <x-authentication-card-logo />
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm px-6 py-8 sm:px-8 sm:py-10
                        prose prose-sm sm:prose-base max-w-none leading-snug
                        prose-headings:font-semibold prose-p:my-1 prose-li:my-0.5 prose-ul:my-2 prose-ol:my-2
                        prose-h2:mt-4 prose-h2:mb-2 prose-h3:mt-3 prose-h3:mb-1.5">
                {!! $terms !!}
            </div>
        </div>
    </div>
</x-guest-layout>
