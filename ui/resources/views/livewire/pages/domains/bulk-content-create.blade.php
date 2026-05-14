<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('content.bulk_create') }}
            </h2>
            <a
                href="{{ route('dashboard') }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
                &larr; {{ __('content.back_to_domains') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ── Content form ─────────────────────────────────────────── --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('content.heading') }}</h3>

                <form wire:submit="createContent" class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">{{ __('content.title_label') }}</label>
                        <input
                            wire:model="form.title"
                            type="text"
                            id="title"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="{{ __('content.title_placeholder') }}"
                        />
                        @error('form.title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="body" class="block text-sm font-medium text-gray-700">{{ __('content.body_label') }}</label>
                        <textarea
                            wire:model="form.body"
                            id="body"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="{{ __('content.body_placeholder') }}"
                        ></textarea>
                        @error('form.body')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-1">
                        <button
                            type="submit"
                            @if(count($selectedDomains) === 0 || $submitting) disabled @endif
                            class="w-full flex justify-center items-center gap-2 px-6 py-2.5 rounded-md font-semibold text-sm text-white transition-colors
                                {{ count($selectedDomains) === 0 || $submitting
                                    ? 'bg-gray-300 cursor-not-allowed'
                                    : 'bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2' }}"
                        >
                            <span wire:loading.remove wire:target="createContent">
                                @if(count($selectedDomains) > 0)
                                    {{ trans_choice('content.create_button_count', count($selectedDomains), ['count' => count($selectedDomains)]) }}
                                @else
                                    {{ __('content.create_button') }}
                                @endif
                            </span>
                            <span wire:loading wire:target="createContent" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ __('content.creating') }}
                            </span>
                        </button>
                    </div>
                </form>

                {{-- ── Results ──────────────────────────────────────────── --}}
                @if(count($results) > 0)
                    <div class="mt-6 space-y-2">
                        <h4 class="text-sm font-semibold text-gray-700">{{ __('content.results') }}</h4>
                        @foreach($results as $result)
                            <div class="flex items-center justify-between p-3 rounded-md {{ $result['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                                <div>
                                    <span class="text-sm font-medium {{ $result['success'] ? 'text-green-800' : 'text-red-800' }}">
                                        {{ $result['domain'] }}
                                    </span>
                                    <span class="text-xs {{ $result['success'] ? 'text-green-600' : 'text-red-600' }} ml-2">
                                        ({{ $result['network'] }})
                                    </span>
                                </div>
                                <span class="text-sm {{ $result['success'] ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $result['message'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ── Domain selection ─────────────────────────────────────── --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('content.select_domains') }}</h3>

                <x-domains.table
                    :domains="$filteredDomains"
                    :selected-domains="$selectedDomains"
                    :network-options="$networkOptions"
                    :loading="$loading"
                    :compact="true"
                />
            </div>
        </div>
    </div>
</div>
