@php
    $labels = ['Wajah Lurus', 'Tengok Kanan', 'Tengok Kiri', 'Senyum'];
    $photoCount = count($photos ?? []);
@endphp

<div
    x-data="{
        current: 0,
        total: {{ $photoCount }},
        photos: @js($photos ?? []),
        labels: @js(array_slice($labels, 0, $photoCount)),
        next() { this.current = (this.current + 1) % this.total },
        prev() { this.current = (this.current - 1 + this.total) % this.total },
    }"
    x-on:keydown.arrow-right.window="next()"
    x-on:keydown.arrow-left.window="prev()"
    class="flex flex-col items-center gap-4 py-2"
>
    {{-- Photo Display --}}
    <div class="relative w-full max-w-sm mx-auto">

        {{-- Image Container --}}
        <div class="relative aspect-square rounded-2xl overflow-hidden bg-gray-900 border border-gray-700 shadow-xl">
            <template x-for="(photo, index) in photos" :key="index">
                <img
                    :src="photo"
                    :alt="labels[index] ?? ('Foto ' + (index + 1))"
                    x-show="current === index"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute inset-0 w-full h-full object-cover"
                >
            </template>

            {{-- Left Arrow --}}
            <button
                x-show="total > 1"
                @click="prev()"
                class="absolute left-2 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-black/50 hover:bg-black/70 backdrop-blur-sm flex items-center justify-center text-white transition-all duration-200 hover:scale-110"
                title="Foto sebelumnya"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </button>

            {{-- Right Arrow --}}
            <button
                x-show="total > 1"
                @click="next()"
                class="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-black/50 hover:bg-black/70 backdrop-blur-sm flex items-center justify-center text-white transition-all duration-200 hover:scale-110"
                title="Foto berikutnya"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            </button>

            {{-- Label Badge (bottom center) --}}
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/60 backdrop-blur-sm text-white text-xs font-semibold shadow-lg">
                    <span class="w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center text-[10px] font-bold" x-text="current + 1"></span>
                    <span x-text="labels[current] ?? ('Foto ' + (current + 1))"></span>
                </span>
            </div>
        </div>
    </div>

    {{-- Dot Indicators --}}
    <div x-show="total > 1" class="flex items-center gap-2">
        <template x-for="(_, index) in photos" :key="'dot-'+index">
            <button
                @click="current = index"
                :class="current === index ? 'w-6 bg-indigo-500' : 'w-2 bg-gray-500 hover:bg-gray-400'"
                class="h-2 rounded-full transition-all duration-300"
            ></button>
        </template>
    </div>

    {{-- Info --}}
    <div class="text-center">
        <p class="text-xs text-gray-400">
            <span class="inline-flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-emerald-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                <span class="text-emerald-400 font-semibold">Terenkripsi AES-256</span>
            </span>
            &middot; Gunakan ← → untuk navigasi
        </p>
    </div>
</div>
