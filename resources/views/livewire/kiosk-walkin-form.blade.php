<div class="walkin-form-container">
    <style>
        .walkin-form-container {
            color: var(--text-primary);
            font-size: 0.95rem;
            text-align: left;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .form-control {
            width: 100%;
            background: rgba(14, 25, 55, 0.6);
            border: 1px solid var(--border-subtle);
            color: var(--text-primary);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            outline: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        .form-control:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }
        .form-control::placeholder {
            color: var(--text-muted);
        }
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%238899bb'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.25em;
        }
        select.form-control option {
            background-color: var(--bg-card);
            color: var(--text-primary);
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn {
            flex: 1;
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: var(--accent-primary);
            color: #fff;
        }
        .btn-primary:hover {
            background: var(--accent-glow);
            box-shadow: var(--shadow-glow);
        }
        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--text-muted);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
        }
        .text-danger {
            color: var(--accent-rose);
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: block;
        }
        .pic-search-results {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            margin-top: 0.5rem;
            max-height: 200px;
            overflow-y: auto;
        }
        .pic-result-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background 0.2s;
        }
        .pic-result-item:last-child {
            border-bottom: none;
        }
        .pic-result-item:hover {
            background: var(--bg-card-hover);
        }
        .selected-pic-card {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .selected-pic-card .name {
            font-weight: 600;
            color: #10b981;
        }
        .selected-pic-card button {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: underline;
        }
        .selected-pic-card button:hover {
            color: var(--text-primary);
        }
        /* Step Indicator */
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 1rem;
        }
        .step-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--text-muted);
            transition: all 0.3s;
        }
        .step-dot.active {
            background: var(--accent-primary);
            box-shadow: 0 0 10px var(--accent-primary);
            transform: scale(1.2);
        }
        .step-line {
            width: 50px;
            height: 2px;
            background: var(--text-muted);
        }
    </style>

    @if (session()->has('general'))
        <div style="background: rgba(244, 63, 94, 0.1); border: 1px solid var(--accent-rose); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #fff;">
            {{ session('general') }}
        </div>
    @endif

    @error('general')
        <div style="background: rgba(244, 63, 94, 0.1); border: 1px solid var(--accent-rose); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #fff;">
            {{ $message }}
        </div>
    @enderror
    
    @if (session()->has('general_success'))
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--accent-green, #10b981); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #fff;">
            {{ session('general_success') }}
        </div>
    @endif

    @if ($step > 0)
        <div class="step-indicator">
            <div class="step-dot {{ $step === 1 ? 'active' : '' }}"></div>
            <div class="step-line"></div>
            <div class="step-dot {{ $step === 2 ? 'active' : '' }}"></div>
        </div>
    @endif

    @if ($step === 0)
        <!-- Step 0: Pemilihan Visitor -->
        <div class="modal-title" style="font-size: 1.25rem; margin-bottom: 0.5rem; text-align: center;">Selamat Datang</div>
        <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">Apakah Anda sudah pernah berkunjung ke sini sebelumnya?</p>
        
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <button type="button" class="btn" style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); color: var(--text-primary); padding: 1.5rem;" wire:click="setReturningVisitor">
                <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.25rem;">Sudah Pernah Berkunjung</div>
                <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: normal;">Gunakan Face Scan untuk isi data otomatis</div>
            </button>
            <button type="button" class="btn" style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 1.5rem;" wire:click="setNewVisitor">
                <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.25rem;">Belum Pernah Berkunjung</div>
                <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: normal;">Isi form data diri dan daftar wajah baru</div>
            </button>
        </div>
    @endif

    @if ($step === 1)
        <!-- Step 1: Data Diri Tamu -->
        <div class="modal-title" style="font-size: 1.25rem; margin-bottom: 1.5rem; text-align: center;">Informasi Tamu</div>
        
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" wire:model="name" class="form-control" placeholder="Cth: Daffa Dewantara">
            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Instansi / Perusahaan</label>
            <input type="text" wire:model="company" class="form-control" placeholder="Cth: PT XYZ Indonesia">
            @error('company') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>No. Handphone (WA)</label>
            <input type="tel" wire:model="phone" class="form-control" placeholder="Cth: 08123456789">
            @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Jumlah Rombongan</label>
            <input type="number" wire:model="pax" class="form-control" min="1" max="50">
            @error('pax') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" wire:click="previousStep">⬅ Kembali</button>
            <button type="button" class="btn btn-primary" wire:click="nextStep">Selanjutnya ➔</button>
        </div>
    @endif

    @if ($step === 2)
        <!-- Step 2: Tujuan Kunjungan -->
        <div class="modal-title" style="font-size: 1.25rem; margin-bottom: 1.5rem; text-align: center;">Tujuan Kunjungan</div>
        
        <div class="form-group">
            <label>Tipe Kunjungan</label>
            <div style="display: flex; gap: 1rem; background: rgba(0,0,0,0.2); padding: 0.8rem 1rem; border-radius: 8px; border: 1px solid var(--border-subtle);">
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-primary); font-weight: normal; cursor: pointer; margin-bottom: 0;">
                    <input type="radio" wire:model.live="visit_type" value="walk-in" style="accent-color: var(--accent-primary); width: 1.2rem; height: 1.2rem;">
                    Bertamu Sekarang
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-primary); font-weight: normal; cursor: pointer; margin-bottom: 0;">
                    <input type="radio" wire:model.live="visit_type" value="appointment" style="accent-color: var(--accent-primary); width: 1.2rem; height: 1.2rem;">
                    Buat Janji (Hari Lain)
                </label>
            </div>
            @error('visit_type') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        @if($visit_type === 'appointment')
            <div style="display: flex; gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label>Tanggal Kunjungan</label>
                    <input type="date" wire:model="visit_date" class="form-control" style="color-scheme: dark;">
                    @error('visit_date') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label>Jam</label>
                    <input type="time" wire:model="visit_time" class="form-control" style="color-scheme: dark;">
                    @error('visit_time') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>
        @endif

        <div class="form-group">
            <label>Departemen Tujuan</label>
            <select wire:model.live="department_id" class="form-control">
                <option value="">-- Pilih Departemen --</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
            @error('department_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>PIC / Karyawan yang Dituju</label>
            
            @if(!$department_id)
                <input type="text" class="form-control" placeholder="Pilih departemen terlebih dahulu..." disabled style="opacity: 0.5;">
            @elseif($selected_pic_id)
                <div class="selected-pic-card">
                    <div>
                        <div style="font-size: 0.8rem; color: #8899bb;">Karyawan Terpilih:</div>
                        <div class="name">👤 {{ $selected_pic_name }}</div>
                    </div>
                    <button type="button" wire:click="resetPicSelection">Ubah</button>
                </div>
            @else
                <input type="text" wire:model.live.debounce.500ms="search_pic" class="form-control" placeholder="Ketik nama karyawan... (min. 2 huruf)">
                
                @if($is_searching)
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">Mencari...</div>
                @elseif(strlen($search_pic) >= 2)
                    @if($search_status === 'found')
                        <div class="pic-search-results">
                            @foreach($pic_results as $pic)
                                <div class="pic-result-item" wire:click="selectPic({{ $pic['id'] }}, '{{ addslashes($pic['name']) }}')">
                                    <div style="font-weight: 500;">{{ $pic['name'] }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Klik untuk memilih</div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($search_status === 'typing')
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem; font-style: italic;">
                            Teruskan mengetik nama karyawan secara spesifik...
                        </div>
                    @elseif($search_status === 'not_found')
                        <div style="font-size: 0.85rem; color: var(--accent-gold); margin-top: 0.5rem; padding: 0.5rem; background: rgba(251, 191, 36, 0.1); border-radius: 4px;">
                            Mohon maaf, nama karyawan tidak ditemukan pada departemen tersebut. Silakan hubungi Resepsionis.
                        </div>
                    @endif
                @endif
            @endif
            @error('selected_pic_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Keperluan</label>
            <textarea wire:model="purpose" class="form-control" rows="3" placeholder="Tuliskan tujuan kunjungan Anda dengan jelas..."></textarea>
            @error('purpose') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" wire:click="previousStep">⬅ Kembali</button>
            <button type="button" class="btn btn-primary" wire:click="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">Selesaikan Registrasi</span>
                <span wire:loading wire:target="submit">Memproses...</span>
            </button>
        </div>
    @endif
</div>
