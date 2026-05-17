<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <!-- Tombol Kembali -->
                <a href="{{ route('dashboard') }}" class="p-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 transition" title="Kembali ke Dashboard">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('Riwayat Perubahan Data') }}
                </h2>
            </div>
            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                {{ $expense->title }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- INFORMASI PENGELUARAN SAAT INI -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <p class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wider">Status Pengeluaran Saat Ini</p>
                <div class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $expense->title }}</h3>
                        <div class="flex items-center mt-2 space-x-3 text-sm">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                @if($expense->category == 'Makanan') Makanan
                                @elseif($expense->category == 'Transportasi') Transportasi
                                @else Lainnya
                                @endif
                            </span>
                            <span class="text-gray-400 dark:text-gray-500">|</span>
                            <span class="text-gray-500 dark:text-gray-400">Dibuat oleh <strong class="text-gray-700 dark:text-gray-300">{{ $expense->user->name }}</strong></span>
                        </div>
                    </div>
                    <div class="text-left md:text-right">
                        <p class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider font-semibold">Nominal Saat Ini</p>
                        <h4 class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">Rp {{ number_format($expense->amount, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>

            <!-- GARIS WAKTU TIMELINE VERTIKAL -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-bold text-lg text-gray-800 dark:text-gray-100 mb-6 flex items-center">
                    Jejak Perubahan Data (Timeline)
                </h3>

                @if($versions->isEmpty())
                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">Belum ada riwayat tercatat.</p>
                @else
                    <div class="relative pl-6 border-l-2 border-indigo-100 dark:border-gray-700 space-y-8">
                        @foreach($versions as $index => $version)
                            @php
                                // Cek apakah ini versi awal (paling bawah/tertua di array)
                                $isInitialVersion = ($index === count($versions) - 1);
                            @endphp

                            <!-- Titik Lini Masa (Dot) -->
                            <div class="relative">
                                <span class="absolute -left-[31px] top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-white dark:bg-gray-800 border-2 {{ $isInitialVersion ? 'border-emerald-500 ring-4 ring-emerald-100 dark:ring-emerald-950/50' : 'border-indigo-500 ring-4 ring-indigo-100 dark:ring-indigo-950/50' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $isInitialVersion ? 'bg-emerald-500' : 'bg-indigo-500' }}"></span>
                                </span>

                                <!-- Konten Detail Kartu Versi -->
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-5 border border-gray-100 dark:border-gray-800 transition hover:border-indigo-100 dark:hover:border-gray-700">
                                    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                                        <div class="flex items-center space-x-2">
                                            @if($isInitialVersion)
                                                <span class="px-2.5 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 text-[10px] font-bold uppercase rounded">
                                                    Versi 1 (Awal)
                                                </span>
                                            @else
                                                <span class="px-2.5 py-0.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-400 text-[10px] font-bold uppercase rounded">
                                                    Versi Perubahan (Ke-{{ count($versions) - $index }})
                                                </span>
                                            @endif
                                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                Oleh: {{ $version->updater->name }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-400 dark:text-gray-500 font-medium">
                                            {{ $version->created_at->format('d M Y, H:i:s') }}
                                        </span>
                                    </div>

                                    <!-- Detail Data Pada Versi Ini -->
                                    <div class="grid grid-cols-3 gap-4 pt-3 border-t border-gray-200/50 dark:border-gray-800 text-xs">
                                        <div>
                                            <p class="text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold">Nama Item</p>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mt-1 truncate">{{ $version->title }}</p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold">Kategori</p>
                                            <p class="text-sm mt-1 font-semibold text-gray-800 dark:text-gray-200">
                                                @if($version->category == 'Makanan') Makanan
                                                @elseif($version->category == 'Transportasi') Transportasi
                                                @else Lainnya
                                                @endif
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold">Nominal</p>
                                            <p class="text-sm font-bold text-gray-900 dark:text-gray-100 mt-1">Rp {{ number_format($version->amount, 0, ',', '.') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- PANEL AKSI -->
            <div class="flex justify-center pt-2">
                <a href="{{ route('dashboard') }}" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold text-sm rounded-xl shadow-sm transition duration-200 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Kembali ke Dashboard Pengeluaran</span>
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
