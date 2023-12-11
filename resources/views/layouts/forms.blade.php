<x-app-layout>
    {{--<x-slot name="header">
        <div class="flex justify-between">
            <div class="flex">
                <x-nav-link class="mr-2" :href="route('forms')" :active="request()->routeIs('forms') || request()->routeIs('dashboard')">
                    {{ __('Forms') }}
                </x-nav-link>
                <x-nav-link :href="route('templates')" :active="request()->routeIs('templates')">
                    {{ __('Templates') }}
                </x-nav-link>
                <x-nav-link :href="route('entries')" :active="request()->routeIs('entries')">
                    {{ __('Entries') }}
                </x-nav-link>
            </div>

            <div class="py-4">
                <a href="{{ route('create-form') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 ml-3 float-right bg-green-500 focus:border-green-900 hover:bg-green-600 active:bg-green-900">
                    {{ __('Create Form') }}
                </a>
            </div>
        </div>
    </x-slot> --}}

    @yield('page-header')

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" style="background: transparent;     min-height: calc(100vh - 161px); overflow-y: auto;">
                <div class="bg-white" style="background: transparent;">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
