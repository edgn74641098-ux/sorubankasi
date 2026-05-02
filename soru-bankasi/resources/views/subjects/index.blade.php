<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dersler
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($subjects->isEmpty())
                        <div>Henüz aktif ders yok.</div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($subjects as $subject)
                                <div class="border rounded p-4">
                                    <div class="font-semibold">{{ $subject->name }}</div>
                                    <div class="text-sm text-gray-600 mt-1">Slug: {{ $subject->slug }}</div>
                                    <div class="mt-3">
                                        <button class="px-4 py-2 bg-indigo-600 text-white rounded opacity-60 cursor-not-allowed" disabled>
                                            Test Başlat (yakında)
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

