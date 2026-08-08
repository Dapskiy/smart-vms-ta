@php
    $stepNames = ['Wajah Lurus', 'Tengok Kanan', 'Tengok Kiri', 'Senyum'];
    $rawPhotoList = array_values($photos ?? []);
    $hasAnyPhoto = count($rawPhotoList) > 0;

    // Selalu siapkan 4 slide sesuai tahapan scan wajah
    $slides = [];
    for ($i = 0; $i < 4; $i++) {
        $img = $rawPhotoList[$i] ?? ($rawPhotoList[0] ?? null);
        $slides[] = [
            'label' => $stepNames[$i],
            'image' => $img,
            'isReal' => isset($rawPhotoList[$i]),
        ];
    }
    $uniqId = 'fpc_' . uniqid();
@endphp

<div
    id="{{ $uniqId }}"
    x-data="{
        current: 0,
        total: 4,
        slides: @js($slides),
        go(idx) {
            this.current = (idx + this.total) % this.total;
        },
        next() {
            this.go(this.current + 1);
        },
        prev() {
            this.go(this.current - 1);
        }
    }"
    x-on:keydown.arrow-right.window="next()"
    x-on:keydown.arrow-left.window="prev()"
    class="flex flex-col items-center gap-4 py-2 select-none"
>
    @if($hasAnyPhoto)
        {{-- Photo Display Container --}}
        <div class="relative w-full max-w-sm mx-auto">
            <div class="relative aspect-square rounded-2xl overflow-hidden bg-gray-900 border border-gray-700 shadow-2xl">
                
                {{-- Main Active Image --}}
                <img
                    :src="slides[current].image"
                    :alt="slides[current].label"
                    class="w-full h-full object-cover transition-opacity duration-200"
                />

                {{-- Left Arrow Button --}}
                <button
                    type="button"
                    x-on:click.stop="prev()"
                    class="absolute left-3 top-1/2 -translate-y-1/2 z-30 w-10 h-10 rounded-full bg-black/70 hover:bg-black/90 active:scale-95 text-white flex items-center justify-center transition-all duration-200 shadow-2xl border border-white/20 cursor-pointer pointer-events-auto"
                    title="Foto sebelumnya (←)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>

                {{-- Right Arrow Button --}}
                <button
                    type="button"
                    x-on:click.stop="next()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 z-30 w-10 h-10 rounded-full bg-black/70 hover:bg-black/90 active:scale-95 text-white flex items-center justify-center transition-all duration-200 shadow-2xl border border-white/20 cursor-pointer pointer-events-auto"
                    title="Foto berikutnya (→)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>

                {{-- Position Label Badge (bottom center) --}}
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-30 pointer-events-none">
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-black/80 backdrop-blur-md text-white text-xs font-semibold shadow-xl border border-white/15">
                        <span class="w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center text-[10px] font-bold" x-text="current + 1"></span>
                        <span x-text="slides[current].label"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Dot Indicators (4 Dots Always) --}}
        <div class="flex items-center gap-2 z-30 pointer-events-auto">
            <template x-for="(slide, index) in slides" :key="index">
                <button
                    type="button"
                    x-on:click.stop="go(index)"
                    :class="current === index ? 'w-7 bg-indigo-500' : 'w-2.5 bg-gray-600 hover:bg-gray-400'"
                    class="h-2.5 rounded-full transition-all duration-300 cursor-pointer"
                    :title="slide.label"
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
            <span class="ml-1">&middot; Gunakan panah ← → untuk navigasi (4 Posisi)</span>
        </div>
    @else
        <div class="py-8 text-center text-gray-400">
            <p>Foto wajah belum tersedia untuk pengunjung ini.</p>
        </div>
    @endif
</div>
