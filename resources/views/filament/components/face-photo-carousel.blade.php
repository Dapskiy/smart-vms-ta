@php
    $defaultLabels = ['Wajah Lurus', 'Tengok Kanan', 'Tengok Kiri', 'Senyum'];
    $photoList = array_values($photos ?? []);
    $photoCount = count($photoList);
    $labels = [];
    for ($i = 0; $i < $photoCount; $i++) {
        $labels[] = $defaultLabels[$i] ?? ('Foto ' . ($i + 1));
    }
@endphp

<div
    x-data="{
        current: 0,
        total: {{ $photoCount }},
        photos: @js($photoList),
        labels: @js($labels),
        next() { if (this.total > 1) this.current = (this.current + 1) % this.total; },
        prev() { if (this.total > 1) this.current = (this.current - 1 + this.total) % this.total; }
    }"
    x-on:keydown.arrow-right.window="next()"
    x-on:keydown.arrow-left.window="prev()"
    class="flex flex-col items-center gap-4 py-2 select-none"
>
    @if($photoCount > 0)
        {{-- Photo Display --}}
        <div class="relative w-full max-w-sm mx-auto">
            <div class="relative aspect-square rounded-2xl overflow-hidden bg-gray-900 border border-gray-700 shadow-xl">
                {{-- Main Active Image --}}
                <img
                    :src="photos[current]"
                    :alt="labels[current]"
                    class="w-full h-full object-cover transition-all duration-300"
                />

                {{-- Left Arrow --}}
                <button
                    x-show="total > 1"
                    type="button"
                    @click="prev()"
                    class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-lg border border-white/10 cursor-pointer"
                    title="Foto sebelumnya (←)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>

                {{-- Right Arrow --}}
                <button
                    x-show="total > 1"
                    type="button"
                    @click="next()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-lg border border-white/10 cursor-pointer"
                    title="Foto berikutnya (→)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>

                {{-- Label Badge (bottom center) --}}
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20">
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-black/70 backdrop-blur-md text-white text-xs font-semibold shadow-xl border border-white/10">
                        <span class="w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center text-[10px] font-bold" x-text="current + 1"></span>
                        <span x-text="labels[current]"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Dot Indicators --}}
        <div x-show="total > 1" class="flex items-center gap-2">
            <template x-for="(_, index) in photos" :key="index">
                <button
                    type="button"
                    @click="current = index"
                    :class="current === index ? 'w-7 bg-indigo-500' : 'w-2.5 bg-gray-600 hover:bg-gray-400'"
                    class="h-2.5 rounded-full transition-all duration-300 cursor-pointer"
                ></button>
            </template>
        </div>

        {{-- Footer Info --}}
        <div class="text-center text-xs text-gray-400">
            <span class="inline-flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-emerald-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                <span class="text-emerald-400 font-semibold">Terenkripsi AES-256</span>
            </span>
            <span x-show="total > 1" class="ml-1">&middot; Gunakan tombol ← → untuk berpindah foto</span>
        </div>
    @else
        <div class="py-8 text-center text-gray-400">
            <p>Foto wajah belum tersedia untuk pengunjung ini.</p>
        </div>
    @endif
</div>
