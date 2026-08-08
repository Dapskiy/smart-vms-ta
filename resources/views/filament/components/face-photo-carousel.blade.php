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
    class="flex flex-col items-center justify-center gap-1.5 p-0 select-none text-center max-w-full overflow-hidden"
>
    @if($hasAnyPhoto)
        {{-- Compact Forced-Size Photo Display + Nav Buttons Side-by-Side --}}
        <div class="flex items-center justify-center gap-2 w-full my-0.5">
            
            {{-- Tombol Kiri < --}}
            <button
                type="button"
                x-on:click.stop="prev()"
                class="w-9 h-9 rounded-full bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-black text-lg flex items-center justify-center transition-all duration-150 shadow-md border border-indigo-400/30 cursor-pointer flex-shrink-0"
                title="Foto sebelumnya (<)"
            >
                &lt;
            </button>

            {{-- Image Box (Forced compact size 180px x 180px - fits completely inside modal) --}}
            <div class="relative w-44 h-44 sm:w-48 sm:h-48 max-h-[35vh] rounded-xl overflow-hidden bg-gray-900 border border-gray-700 shadow-lg flex-shrink-0">
                <img
                    :src="slides[current].image"
                    :alt="slides[current].label"
                    class="w-full h-full object-cover"
                />

                {{-- Position Label Badge --}}
                <div class="absolute bottom-1.5 left-1/2 -translate-x-1/2 z-20">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-black/85 backdrop-blur-md text-white text-[11px] font-semibold shadow-md border border-white/20">
                        <span class="w-3.5 h-3.5 rounded-full bg-indigo-500 flex items-center justify-center text-[9px] font-bold" x-text="current + 1"></span>
                        <span x-text="slides[current].label"></span>
                    </span>
                </div>
            </div>

            {{-- Tombol Kanan > --}}
            <button
                type="button"
                x-on:click.stop="next()"
                class="w-9 h-9 rounded-full bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-black text-lg flex items-center justify-center transition-all duration-150 shadow-md border border-indigo-400/30 cursor-pointer flex-shrink-0"
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
                    :class="current === index ? 'w-5 bg-indigo-500' : 'w-2 bg-gray-600 hover:bg-gray-400'"
                    class="h-1.5 rounded-full transition-all duration-300 cursor-pointer"
                    :title="slide.label"
                ></button>
            </template>
        </div>

        {{-- Footer Info --}}
        <div class="text-[10px] text-gray-400 mt-0.5">
            <span class="text-emerald-400 font-semibold">🔒 Terenkripsi AES-256</span>
            <span class="ml-1">&middot; Tekan <b class="text-gray-200">&lt;</b> / <b class="text-gray-200">&gt;</b> untuk ganti foto</span>
        </div>
    @else
        <div class="py-4 text-center text-gray-400 text-xs">
            <p>Foto wajah belum tersedia untuk pengunjung ini.</p>
        </div>
    @endif
</div>
