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
    style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 4px 0; user-select: none; text-align: center; width: 100%; overflow: hidden;"
>
    @if($hasAnyPhoto)
        {{-- Side-by-Side Container: < [Foto 220x220] > --}}
        <div style="display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; margin: 4px 0;">
            
            {{-- Tombol Kiri < --}}
            <button
                type="button"
                x-on:click.stop="prev()"
                style="width: 44px; height: 44px; border-radius: 50%; background-color: #4f46e5; color: #ffffff; font-size: 22px; font-weight: 900; display: flex; align-items: center; justify-content: center; border: 2px solid #818cf8; cursor: pointer; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.3); transition: transform 0.15s;"
                title="Foto sebelumnya (<)"
            >
                &lt;
            </button>

            {{-- Image Box (Strictly Forced 220px x 220px) --}}
            <div style="position: relative; width: 220px; height: 220px; border-radius: 16px; overflow: hidden; background-color: #0f172a; border: 2px solid #334155; flex-shrink: 0; box-shadow: 0 8px 20px rgba(0,0,0,0.35);">
                <img
                    :src="slides[current].image"
                    :alt="slides[current].label"
                    style="width: 100%; height: 100%; object-fit: cover; display: block;"
                />

                {{-- Position Label Badge --}}
                <div style="position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%); z-index: 20; white-space: nowrap; pointer-events: none;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 9999px; background: rgba(0,0,0,0.85); color: #ffffff; font-size: 11px; font-weight: 600; border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 4px 10px rgba(0,0,0,0.4);">
                        <span style="width: 16px; height: 16px; border-radius: 50%; background: #6366f1; display: inline-flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 700; color: #fff;" x-text="current + 1"></span>
                        <span x-text="slides[current].label"></span>
                    </span>
                </div>
            </div>

            {{-- Tombol Kanan > --}}
            <button
                type="button"
                x-on:click.stop="next()"
                style="width: 44px; height: 44px; border-radius: 50%; background-color: #4f46e5; color: #ffffff; font-size: 22px; font-weight: 900; display: flex; align-items: center; justify-content: center; border: 2px solid #818cf8; cursor: pointer; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.3); transition: transform 0.15s;"
                title="Foto berikutnya (>)"
            >
                &gt;
            </button>
        </div>

        {{-- Dot Indicators --}}
        <div style="display: flex; align-items: center; gap: 6px; margin-top: 2px;">
            <template x-for="(slide, index) in slides" :key="index">
                <button
                    type="button"
                    x-on:click.stop="go(index)"
                    :style="current === index ? 'width: 24px; background-color: #6366f1;' : 'width: 8px; background-color: #475569;'"
                    style="height: 7px; border-radius: 9999px; border: none; cursor: pointer; transition: all 0.3s;"
                    :title="slide.label"
                ></button>
            </template>
        </div>

        {{-- Footer Info --}}
        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
            <span style="color: #34d399; font-weight: 600;">🔒 Terenkripsi AES-256</span>
            <span style="margin-left: 4px;">&middot; Klik <b style="color: #818cf8;">&lt;</b> atau <b style="color: #818cf8;">&gt;</b> untuk ganti foto</span>
        </div>
    @else
        <div style="padding: 24px 0; text-align: center; color: #94a3b8; font-size: 13px;">
            <p>Foto wajah belum tersedia untuk pengunjung ini.</p>
        </div>
    @endif
</div>
