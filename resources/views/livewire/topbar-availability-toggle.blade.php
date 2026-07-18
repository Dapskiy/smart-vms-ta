<div class="flex items-center mr-4">
    <button
        wire:click="toggle"
        type="button"
        class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border text-xs font-semibold shadow-sm transition-all duration-200 select-none outline-none focus:outline-none hover:scale-105 active:scale-95
        {{ $isAvailable 
            ? 'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:border-emerald-800/50 dark:text-emerald-400' 
            : 'bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/30 dark:border-rose-800/50 dark:text-rose-400' }}"
    >
        {{-- Status indicator dot --}}
        <span class="relative flex h-2 w-2">
            @if($isAvailable)
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            @else
                <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
            @endif
        </span>

        <span>{{ $isAvailable ? 'Tersedia untuk Tamu' : 'Sedang Sibuk / Istirahat' }}</span>
    </button>
</div>
