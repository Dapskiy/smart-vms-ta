<div>
    <div style="margin-bottom: 1.5rem;">
        <label for="checkin_token" style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">{{ $lang === 'en' ? 'Reservation Token Code' : 'Kode Token Reservasi' }}</label>
        <input type="text" id="checkin_token" wire:model.defer="token" placeholder="{{ $lang === 'en' ? 'Enter 6-10 digit token' : 'Masukkan 6-10 digit token' }}" autocomplete="off"
            style="width: 100%; padding: 0.9rem 1.2rem; border-radius: 0.75rem; border: 1px solid #cbd5e1; font-size: 1.2rem; text-align: center; letter-spacing: 2px; font-weight: 700; color: #0f172a; background: #f8fafc; transition: all 0.2s;"
            onfocus="this.style.borderColor='#6366f1'; this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)';"
            onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
        
        @if($errorMessage)
            <div style="margin-top: 0.75rem; padding: 0.75rem; border-radius: 0.5rem; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; font-size: 0.8rem; display: flex; align-items: flex-start; gap: 0.5rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.2rem; height: 1.2rem; flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                <span>{{ $errorMessage }}</span>
            </div>
        @endif
    </div>

    <button wire:click="submitToken" wire:loading.attr="disabled" class="btn-ok" style="width: 100%; position: relative;">
        <span wire:loading.remove wire:target="submitToken">{{ $lang === 'en' ? 'VERIFY & CHECK-IN' : 'VERIFIKASI & CHECK-IN' }}</span>
        <span wire:loading wire:target="submitToken">
            <svg style="width: 1.2rem; height: 1.2rem; display: inline-block; animation: spin 1s linear infinite; margin-right: 0.5rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="30" stroke-dashoffset="10"/></svg>
            {{ $lang === 'en' ? 'PROCESSING...' : 'MEMPROSES...' }}
        </span>
    </button>
</div>
