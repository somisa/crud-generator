<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-col gap-6 md:flex-row">
                @include('admin.sidebar')

                <div class="md:w-3/4">
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 font-semibold text-gray-800">Dashboard</div>

                        <div class="p-6 text-gray-900">
                            Your application's dashboard.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
