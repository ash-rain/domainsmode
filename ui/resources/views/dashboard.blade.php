<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('domains.management') }}
            </h2>
            @php $selectedCount = count(session('selected_domains', [])); @endphp
            <a
                href="{{ route('domains.bulk-create') }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
            >
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('content.bulk_create_button') }}
                @if($selectedCount > 0)
                    <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold bg-white text-indigo-600 rounded-full">
                        {{ $selectedCount }}
                    </span>
                @endif
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <livewire:domains.domains-list />
            </div>
        </div>
    </div>
</x-app-layout>
