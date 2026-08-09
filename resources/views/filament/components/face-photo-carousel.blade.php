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

<style>
    .fpc-root {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 8px 0 4px;
        user-select: none;
        text-align: center;
    }
    .fpc-carousel-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
    }
    .fpc-nav-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #4f46e5;
        color: #fff;
        font-size: 20px;
        font-weight: 900;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #818cf8;
        cursor: pointer;
        flex-shrink: 0;
        box-shadow: 0 3px 10px rgba(0,0,0,0.25);
        transition: background 0.15s, transform 0.1s;
        line-height: 1;
    }
    .fpc-nav-btn:hover { background: #6366f1; }
    .fpc-nav-btn:active { transform: scale(0.93); }

    .fpc-photo-frame {
        position: relative;
        width: 200px;
        height: 200px;
        border-radius: 14px;
        overflow: hidden;
        background: #0f172a;
        border: 2px solid #334155;
        flex-shrink: 0;
        box-shadow: 0 6px 18px rgba(0,0,0,0.3);
    }
    .fpc-photo-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .fpc-label-badge {
        position: absolute;
        bottom: 6px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        white-space: nowrap;
        pointer-events: none;
    }
    .fpc-label-badge span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 9px;
        border-radius: 999px;
        background: rgba(0,0,0,0.82);
        color: #fff;
        font-size: 10.5px;
        font-weight: 600;
        border: 1px solid rgba(255,255,255,0.15);
    }
    .fpc-label-num {
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background: #6366f1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 700;
        color: #fff;
    }

    .fpc-dots {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .fpc-dot {
        height: 6px;
        border-radius: 999px;
        border: none;
        cursor: pointer;
        transition: all 0.25s;
    }

    .fpc-footer {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
        font-size: 10.5px;
        color: #94a3b8;
    }
    .fpc-footer .fpc-lock {
        color: #34d399;
        font-weight: 600;
    }

    .fpc-fullview-btn {
        padding: 4px 12px;
        border-radius: 6px;
        background: #1e293b;
        color: #a5b4fc;
        border: 1px solid #334155;
        font-size: 10.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: background 0.2s;
    }
    .fpc-fullview-btn:hover { background: #334155; }

    .fpc-empty {
        padding: 20px 0;
        color: #94a3b8;
        font-size: 13px;
    }
</style>

<div
    id="{{ $uniqId }}"
    x-data="{
        current: 0,
        total: 4,
        slides: @js($slides),
        go(i) { this.current = (i + this.total) % this.total; },
        next() { this.go(this.current + 1); },
        prev() { this.go(this.current - 1); },
        openFull() {
            const s = this.slides[this.current];
            if (!s.image) return;
            const w = window.open('', '_blank');
            if (!w) return;
            w.document.write(`<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Foto — ${s.label}</title><style>body{margin:0;background:#0f172a;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;font-family:system-ui,sans-serif}.b{color:#818cf8;font-weight:700;font-size:14px;margin-bottom:14px;padding:5px 14px;background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.3);border-radius:999px}img{max-width:95vw;max-height:85vh;border-radius:14px;box-shadow:0 20px 50px rgba(0,0,0,.5);border:2px solid #334155;object-fit:contain}</style></head><body><div class='b'>${s.label}</div><img src='${s.image}'/></body></html>`);
            w.document.close();
        }
    }"
    x-on:keydown.arrow-right.window="next()"
    x-on:keydown.arrow-left.window="prev()"
    class="fpc-root"
>
    @if($hasAnyPhoto)
        {{-- < [Foto] > --}}
        <div class="fpc-carousel-row">
            <button type="button" x-on:click.stop="prev()" class="fpc-nav-btn" title="Sebelumnya">&lt;</button>

            <div class="fpc-photo-frame">
                <img :src="slides[current].image" :alt="slides[current].label" />
                <div class="fpc-label-badge">
                    <span>
                        <span class="fpc-label-num" x-text="current + 1"></span>
                        <span x-text="slides[current].label"></span>
                    </span>
                </div>
            </div>

            <button type="button" x-on:click.stop="next()" class="fpc-nav-btn" title="Berikutnya">&gt;</button>
        </div>

        {{-- Dots --}}
        <div class="fpc-dots">
            <template x-for="(s, i) in slides" :key="i">
                <button
                    type="button"
                    x-on:click.stop="go(i)"
                    class="fpc-dot"
                    :style="current === i ? 'width:22px;background:#6366f1' : 'width:7px;background:#475569'"
                    :title="s.label"
                ></button>
            </template>
        </div>

        {{-- Footer --}}
        <div class="fpc-footer">
            <span class="fpc-lock">🔒 AES-256</span>
            <span>&middot;</span>
            <button type="button" x-on:click.stop="openFull()" class="fpc-fullview-btn" title="Buka di tab baru">
                🔍 Lihat Penuh ↗
            </button>
        </div>
    @else
        <div class="fpc-empty">
            <p>Foto wajah belum tersedia.</p>
        </div>
    @endif
</div>
