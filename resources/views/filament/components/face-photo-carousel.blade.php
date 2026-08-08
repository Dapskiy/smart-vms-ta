@php
    $stepNames = ['Wajah Lurus', 'Tengok Kanan', 'Tengok Kiri', 'Senyum'];
    $rawPhotoList = array_values($photos ?? []);
    $hasAnyPhoto = count($rawPhotoList) > 0;

    $slides = [];
    for ($i = 0; $i < 4; $i++) {
        $img = $rawPhotoList[$i] ?? ($rawPhotoList[0] ?? null);
        $slides[] = [
            'label' => $stepNames[$i],
            'image' => $img,
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
    class="flex flex-col items-center justify-center gap-2 p-1 select-none text-center"
>
    @if($hasAnyPhoto)
        {{-- Compact Photo Display + Nav Buttons Side-by-Side --}}
        <div class="flex items-center justify-center gap-3 w-full my-1">
            
            {{-- Tombol Kiri < --}}
            <button
                type="button"
                x-on:click.stop="prev()"
                class="w-10 h-10 rounded-full bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-black text-xl flex items-center justify-center transition-all duration-150 shadow-md border border-indigo-400/30 cursor-pointer flex-shrink-0"
                title="Foto sebelumnya (<)"
            >
                &lt;
            </button>

            {{-- Image Box (Compact size fits screen without scrolling) --}}
            <div class="relative w-60 h-60 sm:w-64 sm:h-64 rounded-2xl overflow-hidden bg-gray-900 border border-gray-700 shadow-xl flex-shrink-0">
                <img
                    :src="slides[current].image"
                    :alt="slides[current].label"
                    class="w-full h-full object-cover transition-opacity duration-200"
                />

                {{-- Position Label Badge --}}
                <div class="absolute bottom-2 left-1/2 -translate-x-1/2 z-20">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/80 backdrop-blur-md text-white text-xs font-semibold shadow-lg border border-white/20">
                        <span class="w-4 h-4 rounded-full bg-indigo-500 flex items-center justify-center text-[10px] font-bold" x-text="current + 1"></span>
                        <span x-text="slides[current].label"></span>
                    </span>
                </div>
            </div>

            {{-- Tombol Kanan > --}}
            <button
                type="button"
                x-on:click.stop="next()"
                class="w-10 h-10 rounded-full bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-black text-xl flex items-center justify-center transition-all duration-150 shadow-md border border-indigo-400/30 cursor-pointer flex-shrink-0"
                title="Foto berikutnya (>)"
            >
                &gt;
            </button>
        </div>

        {{-- Dot Indicators --}}
        <div class="flex items-center gap-1.5 mt-0.5">
            <template x-for="(slide, index) in slides" :key="index">
                <button
                    type="button"
                    x-on:click.stop="go(index)"
                    :class="current === index ? 'w-6 bg-indigo-500' : 'w-2 bg-gray-600 hover:bg-gray-400'"
                    class="h-2 rounded-full transition-all duration-300 cursor-pointer"
                    :title="slide.label"
                ></button>
            </template>
        </div>

        {{-- Footer Info --}}
        <div class="text-[11px] text-gray-400 mt-0.5">
            <span class="text-emerald-400 font-semibold">🔒 Terenkripsi AES-256</span>
            <span class="ml-1">&middot; Tekan <b class="text-gray-200">&lt;</b> atau <b class="text-gray-200">&gt;</b> untuk ganti foto</span>
        </div>
    @else
        <div class="py-6 text-center text-gray-400 text-sm">
            <p>Foto wajah belum tersedia untuk pengunjung ini.</p>
        </div>
    @endif
</div>
