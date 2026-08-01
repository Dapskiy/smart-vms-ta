<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Department;
use App\Models\Pic;
use App\Models\Visitor;
use App\Models\Appointment;
use Illuminate\Support\Str;
use App\Services\VisitIdService;
use App\Mail\PicApprovalMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;

class KioskWalkinForm extends Component
{
    // Language
    public string $lang = 'id';

    #[On('setLang')]
    public function setLanguage($lang)
    {
        $this->lang = $lang;
    }

    // Form fields
    public $name = '';
    public $company = '';
    public $phone = '';
    public $purpose = '';
    public $pax = 1;
    
    // Visit type fields
    public $visit_type = 'walk-in'; // 'walk-in' atau 'appointment'
    public $visit_date = '';
    public $visit_time = '';
    
    // Selection fields
    public $department_id = null;
    public $search_pic = '';
    public $selected_pic_id = null;
    public $selected_pic_name = '';

    // UI state
    public $step = 0; // 0: Pilihan, 1: Data Diri, 2: Tujuan
    public $pic_results = [];
    public $is_searching = false;
    public $search_status = '';
    
    // Auto-fill state
    public $is_verified_returning = false;
    public $verified_visitor_id = null;

    // Pending approval state
    public $pending_approval_token = null;

    // Duplicate face override: user confirmed they are a different person
    public bool $duplicateOverride = false;
    public ?string $duplicateWarningName = null;

    public function mount()
    {
        if (!\App\Helpers\KioskHelper::isKioskLocal()) {
            $this->visit_type = 'appointment';
        }
    }

    #[On('resetWalkinForm')]
    public function resetForm()
    {
        $this->step = 0;
        if (!\App\Helpers\KioskHelper::isKioskLocal()) {
            $this->visit_type = 'appointment';
        } else {
            $this->visit_type = 'walk-in';
        }
        $this->is_verified_returning = false;
        $this->verified_visitor_id = null;
        $this->reset(['name', 'company', 'phone', 'purpose', 'pax', 'department_id', 'search_pic', 'selected_pic_id', 'selected_pic_name', 'pic_results', 'visit_date', 'visit_time']);
        $this->duplicateOverride = false;
        $this->duplicateWarningName = null;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function setNewVisitor()
    {
        $this->step = 1;
        $this->is_verified_returning = false;
        $this->verified_visitor_id = null;
    }

    public function setReturningVisitor()
    {
        $this->dispatch('trigger-face-search');
    }

    #[On('findVisitorByFace')]
    public function findVisitorByFace($descriptor)
    {
        $visitors = Visitor::whereNotNull('face_features')->get();
        $bestMatch = null;
        $bestDistance = 1.0;
        $threshold = 0.5;

        foreach ($visitors as $visitor) {
            $stored = $visitor->face_features ?? [];
            if (!is_array($stored)) continue;
            if (isset($stored[0]) && !is_array($stored[0])) $stored = [$stored];

            foreach ($stored as $storedDescriptor) {
                $dist = $this->euclideanDistance($storedDescriptor, $descriptor);
                if ($dist < $bestDistance) {
                    $bestDistance = $dist;
                    $bestMatch = $visitor;
                }
            }
        }

        if ($bestMatch && $bestDistance <= $threshold) {
            \App\Models\FaceVerificationLog::create([
                'visitor_id' => $bestMatch->id,
                'visitor_name' => $bestMatch->name,
                'type' => 'returning-visitor-lookup',
                'euclidean_distance' => $bestDistance,
                'threshold' => $threshold,
                'is_success' => true,
                'ip_address' => request()->ip(),
            ]);

            $this->name = $bestMatch->name;
            $this->company = $bestMatch->company;
            $this->phone = $bestMatch->phone;
            $this->verified_visitor_id = $bestMatch->id;
            $this->is_verified_returning = true;
            
            $this->step = 2; // Lompat ke tujuan
            $this->dispatch('walkin-form-reopen'); // Buka modal kembali
            session()->flash('general_success', 'Selamat datang kembali, ' . $this->name . '!');
        } else {
            \App\Models\FaceVerificationLog::create([
                'visitor_id' => $bestMatch ? $bestMatch->id : null,
                'visitor_name' => $bestMatch ? $bestMatch->name : 'Unknown',
                'type' => 'returning-visitor-lookup',
                'euclidean_distance' => $bestMatch ? $bestDistance : null,
                'threshold' => $threshold,
                'is_success' => false,
                'error_message' => 'Wajah tidak dikenali. Silakan mendaftar sebagai tamu baru.',
                'ip_address' => request()->ip(),
            ]);

            $this->step = 1; // Paksa jadi tamu baru
            $this->is_verified_returning = false;
            $this->dispatch('walkin-form-reopen');
            $this->addError('general', 'Wajah tidak dikenali. Silakan mendaftar sebagai tamu baru.');
        }
    }

    /**
     * User confirms they are a different person despite face similarity.
     * Sets override flag and re-triggers face scan to continue registration.
     */
    public function confirmDifferentPerson(): void
    {
        $this->duplicateOverride = true;
        $this->duplicateWarningName = null;
        $this->dispatch('retrigger-face-scan');
    }

    public function updatedDepartmentId()
    {
        $this->resetPicSelection();
    }

    public function updatedSearchPic()
    {
        if (strlen($this->search_pic) >= 2 && $this->department_id) {
            $this->is_searching = true;
            
            // Ambil kandidat dari DB menggunakan pencarian LIKE
            $queryResults = Pic::where('department_id', $this->department_id)
                ->where('name', 'iLike', '%' . $this->search_pic . '%')
                ->limit(10) // Ambil agak banyak untuk difilter
                ->get()
                ->toArray();

            $filteredResults = [];
            $searchLower = strtolower(trim($this->search_pic));
            $searchWords = explode(' ', $searchLower);
            
            // Cek apakah input mengandung kata kedua dan panjangnya minimal 2 huruf
            // End() mengambil elemen terakhir dari array (kata terakhir yang diketik)
            $hasSecondWordMin2Chars = count($searchWords) >= 2 && strlen(end($searchWords)) >= 2;

            foreach ($queryResults as $pic) {
                $picNameLower = strtolower(trim($pic['name']));
                $picWords = explode(' ', $picNameLower);

                if (count($picWords) === 1) {
                    // Jika nama PIC di DB hanya 1 kata, munculkan HANYA jika input persis sama
                    if ($searchLower === $picNameLower) {
                        $filteredResults[] = $pic;
                    }
                } else {
                    // Jika nama PIC di DB ada 2 kata atau lebih, 
                    // WAJIB mengetik spasi + minimal 2 huruf di kata kedua
                    if ($hasSecondWordMin2Chars) {
                        $filteredResults[] = $pic;
                    }
                }
            }

            // Batasi 5 untuk UI
            $this->pic_results = array_slice($filteredResults, 0, 5);
            $this->is_searching = false;

            if (count($this->pic_results) > 0) {
                $this->search_status = 'found';
            } elseif (count($queryResults) > 0) {
                // Ada di DB, tapi ter-filter karena syarat "Nama 1 + 2 Huruf Nama 2" belum terpenuhi
                $this->search_status = 'typing';
            } else {
                // Memang tidak ada sama sekali di DB
                $this->search_status = 'not_found';
            }

        } else {
            $this->pic_results = [];
            $this->search_status = '';
        }
    }

    public function selectPic($picId, $picName)
    {
        $pic = Pic::find($picId);
        if ($pic && !$pic->is_available) {
            $isToday = true;
            if ($this->visit_type === 'appointment' && $this->visit_date) {
                $isToday = (date('Y-m-d', strtotime($this->visit_date)) === date('Y-m-d'));
            }
            if ($isToday) {
                $this->addError('selected_pic_id', 'Maaf, PIC ini belum melakukan Check-In (Tidak Hadir). Silakan pilih PIC lain.');
                return;
            }
        }

        $this->selected_pic_id = $picId;
        $this->selected_pic_name = $picName;
        $this->search_pic = '';
        $this->pic_results = [];
        $this->search_status = '';
        $this->resetErrorBag('selected_pic_id');
    }

    public function resetPicSelection()
    {
        $this->selected_pic_id = null;
        $this->selected_pic_name = '';
        $this->search_pic = '';
        $this->pic_results = [];
        $this->search_status = '';
    }

    public function nextStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'name' => 'required|string|max:255',
                'company' => 'required|string|max:255',
                'phone' => 'required|regex:/^[0-9]+$/|min:10|max:15',
                'pax' => 'required|integer|min:1|max:50',
            ], [
                'phone.regex' => 'Nomor HP hanya boleh berisi angka.',
                'phone.min' => 'Nomor HP minimal 10 angka.',
                'phone.max' => 'Nomor HP maksimal 15 angka.',
            ]);
            $this->step = 2;
        }
    }

    public function previousStep()
    {
        if ($this->step > 0) {
            $this->step--;

            if ($this->step === 0) {
                // Reset state saat kembali ke pemilihan awal
                $this->is_verified_returning = false;
                $this->verified_visitor_id = null;
                $this->reset(['name', 'company', 'phone']);
            }
        }
    }

    public function submit()
    {
        if (!$this->checkRateLimit()) {
            return;
        }

        if (!\App\Helpers\KioskHelper::isKioskLocal() && $this->visit_type !== 'appointment') {
            $this->addError('general', 'Akses Terbatas: Fitur walk-in hanya dapat digunakan melalui perangkat Kiosk di kantor.');
            $this->dispatch('walkin-error');
            return;
        }

        $rules = [
            'department_id' => 'required|exists:departments,id',
            'selected_pic_id' => 'required|exists:pics,id',
            'purpose' => 'required|string',
            'visit_type' => 'required|in:walk-in,appointment',
        ];

        if ($this->visit_type === 'appointment') {
            $rules['visit_date'] = 'required|date|after_or_equal:today';
            $rules['visit_time'] = 'required|date_format:H:i';
        }

        $this->validate($rules, [
            'selected_pic_id.required' => 'Pilih karyawan yang akan dituju dari hasil pencarian.'
        ]);

        // Verifikasi tambahan: jika kunjungan hari ini, PIC wajib hadir (sudah check-in)
        $pic = Pic::find($this->selected_pic_id);
        if ($pic && !$pic->is_available) {
            $isToday = true;
            if ($this->visit_type === 'appointment' && $this->visit_date) {
                $isToday = (date('Y-m-d', strtotime($this->visit_date)) === date('Y-m-d'));
            }
            if ($isToday) {
                $this->addError('selected_pic_id', 'Maaf, PIC ini belum melakukan Check-In (Tidak Hadir). Silakan pilih PIC lain atau ganti tanggal kunjungan.');
                return;
            }
        }

        if ($this->is_verified_returning && $this->verified_visitor_id) {
            $visitor = Visitor::find($this->verified_visitor_id);
            if ($visitor && $visitor->is_blacklisted) {
                $this->addError('general', 'Mohon maaf, Anda tidak dapat melanjutkan proses registrasi. Silakan hubungi Resepsionis.');
                return;
            }
            if ($visitor) {
                // Update data jika ada perubahan di backend (opsional, karena form step 1 dilompati)
                $this->createAppointmentRecord($visitor);
            } else {
                $this->addError('general', 'Terjadi kesalahan sistem. Silakan ulangi pendaftaran.');
            }
        } else {
            if (!\App\Helpers\KioskHelper::isKioskLocal()) {
                // Offsite visitor completing appointment without face scan
                $this->submitWithoutFace();
            } else {
                // Berhenti di sini, trigger face scan JS di frontend (pendaftar baru)
                $this->dispatch('trigger-face-scan');
            }
        }
    }

    #[On('submitWithoutFace')]
    public function submitWithoutFace()
    {
        if (!$this->checkRateLimit()) {
            return;
        }

        if (!\App\Helpers\KioskHelper::isKioskLocal() && $this->visit_type !== 'appointment') {
            $this->addError('general', 'Akses Terbatas: Fitur walk-in hanya dapat digunakan melalui perangkat Kiosk di kantor.');
            $this->dispatch('walkin-error');
            return;
        }

        // 1. Simpan/Cari Visitor
        $visitor = Visitor::firstOrCreate(
            ['phone' => $this->phone],
            [
                'name' => $this->name,
                'company' => $this->company,
                'is_blacklisted' => false,
            ]
        );

        // Perbarui atribut dinamis (company, name) jika terdapat perubahan data terbaru
        if ($visitor->company !== $this->company || $visitor->name !== $this->name) {
            $visitor->update([
                'company' => $this->company ?: $visitor->company,
                'name' => $this->name ?: $visitor->name,
            ]);
        }

        if ($visitor->is_blacklisted) {
            $this->addError('general', 'Mohon maaf, Anda tidak dapat melanjutkan proses registrasi. Silakan hubungi Resepsionis.');
            $this->dispatch('walkin-error');
            return;
        }

        $this->createAppointmentRecord($visitor);
    }

    #[On('finalizeWalkin')]
    public function finalizeWalkin($descriptor, $photoBase64)
    {
        if (!$this->checkRateLimit()) {
            return;
        }

        if (!\App\Helpers\KioskHelper::isKioskLocal()) {
            $this->addError('general', 'Akses Terbatas: Fitur ini hanya dapat digunakan melalui perangkat Kiosk di kantor.');
            $this->dispatch('walkin-error');
            return;
        }

        // 1. Simpan/Cari Visitor
        $visitor = Visitor::firstOrCreate(
            ['phone' => $this->phone],
            [
                'name' => $this->name,
                'company' => $this->company,
                // Default false, if existing is true, it won't be overridden by firstOrCreate, which is good.
                'is_blacklisted' => false,
            ]
        );

        // Perbarui atribut dinamis (company, name) jika terdapat perubahan data terbaru
        if ($visitor->company !== $this->company || $visitor->name !== $this->name) {
            $visitor->update([
                'company' => $this->company ?: $visitor->company,
                'name' => $this->name ?: $visitor->name,
            ]);
        }

        if ($visitor->is_blacklisted) {
            $this->addError('general', 'Mohon maaf, Anda tidak dapat melanjutkan proses registrasi. Silakan hubungi Resepsionis.');
            $this->dispatch('walkin-error');
            return;
        }

        // 2. Global Face Duplicate Check (Auto-Merge / Seamless Returning Visitor)
        // Jika pengunjung baru mendaftar dengan wajah yang sudah ada (tapi mungkin salah masuk nomor HP)
        if (!$this->duplicateOverride) {
            $allOtherVisitors = Visitor::whereNotNull('face_features')->where('id', '!=', $visitor->id)->get();
            $globalThreshold = 0.40; // Sama seperti threshold wajar

            foreach ($allOtherVisitors as $otherVisitor) {
                $otherStored = $otherVisitor->face_features ?? [];
                if (!is_array($otherStored)) continue;
                if (isset($otherStored[0]) && !is_array($otherStored[0])) $otherStored = [$otherStored];

                foreach ($otherStored as $otherDescriptor) {
                    if ($this->euclideanDistance($otherDescriptor, $descriptor) <= $globalThreshold) {
                        // Wajah dikenali sebagai visitor lama!
                        // Daripada menampilkan pesan error "Wajah sudah terdaftar",
                        // kita otomatis hubungkan (merge) ke profil lamanya agar lebih cerdas.
                        if ($visitor->wasRecentlyCreated) {
                            $visitor->delete();
                        }
                        $visitor = $otherVisitor;
                        // Perbarui data jika pengunjung mengetik data baru di form
                        $visitor->update([
                            'name' => $this->name ?: $visitor->name,
                            'company' => $this->company ?: $visitor->company,
                            'phone' => $this->phone ?: $visitor->phone,
                        ]);
                        break 2; // Keluar dari kedua loop dan lanjut ke pembuatan janji
                    }
                }
            }
        }

        // 3. Verifikasi Wajah Sendiri (jika sudah punya)
        $existingFeatures = is_array($visitor->face_features) ? $visitor->face_features : [];
        if (!empty($existingFeatures)) {
            $threshold = 0.4;
            $bestDistance = 1.0;
            
            // Compatibility for old format (single descriptor array)
            if (isset($existingFeatures[0]) && !is_array($existingFeatures[0])) {
                $existingFeatures = [$existingFeatures];
            }

            foreach ($existingFeatures as $storedDescriptor) {
                $distance = $this->euclideanDistance($storedDescriptor, $descriptor);
                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                }
            }

            if ($bestDistance > $threshold) {
                 \App\Models\FaceVerificationLog::create([
                     'visitor_id' => $visitor->id,
                     'visitor_name' => $visitor->name,
                     'type' => 'walkin-verify',
                     'euclidean_distance' => $bestDistance,
                     'threshold' => $threshold,
                     'is_success' => false,
                     'error_message' => 'Wajah tidak cocok dengan data pendaftaran sebelumnya.',
                     'ip_address' => request()->ip(),
                 ]);

                 $this->addError('general', 'Wajah tidak cocok dengan data pendaftaran sebelumnya.');
                 $this->dispatch('walkin-error'); 
                 return;
            }
        }

        // 3. Simpan data wajah baru (append max 10)
        try {
            $isNewRegistration = empty($visitor->face_features);

            $existingPhotos = is_array($visitor->face_photo) ? $visitor->face_photo : [];
            if (count($existingPhotos) < 10) {
                $existingPhotos[] = $photoBase64;
                $visitor->face_photo = $existingPhotos;
            }
            if (count($existingFeatures) < 10) {
                $existingFeatures[] = $descriptor;
                $visitor->face_features = $existingFeatures;
            }
            $visitor->save();

            \App\Models\FaceVerificationLog::create([
                'visitor_id' => $visitor->id,
                'visitor_name' => $visitor->name,
                'type' => $isNewRegistration ? 'walkin-register' : 'walkin-verify',
                'euclidean_distance' => $isNewRegistration ? null : ($bestDistance ?? null),
                'threshold' => $isNewRegistration ? 0.5 : ($threshold ?? 0.4),
                'is_success' => true,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // Ignore error log
        }

        $this->createAppointmentRecord($visitor);
    }

    private function checkRateLimit()
    {
        $ip = request()->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts('submit-appointment:'.$ip, 3)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn('submit-appointment:'.$ip);
            $this->addError('general', "Terlalu banyak mencoba. Silakan tunggu {$seconds} detik sebelum mengirim formulir pendaftaran baru.");
            $this->dispatch('walkin-error');
            return false;
        }
        return true;
    }

    private function createAppointmentRecord(Visitor $visitor)
    {
        $isWalkIn = $this->visit_type === 'walk-in';

        $appointment = Appointment::create([
            'visit_id'       => VisitIdService::generate(),
            'visitor_id'     => $visitor->id,
            'pic_id'         => $this->selected_pic_id,
            'type'           => $this->visit_type,
            'status'         => $isWalkIn ? 'active' : 'pending',
            'visit_date'     => $isWalkIn ? now()->toDateString() : $this->visit_date,
            'visit_time'     => $isWalkIn ? now()->toTimeString() : $this->visit_time,
            'checkin_time'   => $isWalkIn ? now()->format('H:i:s') : null,
            'purpose'        => $this->purpose,
            'pax'            => $this->pax,
            'token'          => Str::random(10),
            'approval_token' => $isWalkIn ? null : Str::uuid()->toString(),
        ]);

        // Hit rate limiter setelah record sukses dibuat
        $ip = request()->ip();
        \Illuminate\Support\Facades\RateLimiter::hit('submit-appointment:'.$ip, 3600); // 1 jam decay

        // Load relasi untuk data display
        $appointment->load(['visitor', 'pic.department']);

        if ($isWalkIn) {
            // Walk-in: PIC sudah pasti hadir (sudah absen), jadi AUTO-ACC — langsung success!
            // Kirim email NOTIFIKASI saja ke PIC (tanpa tombol Terima/Tolak)
            $picEmail = $appointment->pic?->email;
            if ($picEmail) {
                Mail::to($picEmail)->send(new \App\Mail\PicWalkinNotificationMail($appointment));
            }

            // Langsung dispatch Pop-up Success ke Kiosk (tanpa layar menunggu 120 detik)
            $appointmentData = [
                'visitorName' => $this->name,
                'company'     => $this->company,
                'phone'       => $this->phone,
                'picName'     => $this->selected_pic_name,
                'department'  => $appointment->pic?->department?->name ?? '-',
                'visit_date'  => \Carbon\Carbon::parse($appointment->visit_date)->translatedFormat('d F Y'),
                'visit_time'  => $appointment->visit_time,
                'purpose'     => $appointment->purpose,
                'type'        => $appointment->type,
            ];
            $this->dispatch('walkin-success', appt: $appointmentData);
        } else {
            // Appointment biasa — langsung tampilkan konfirmasi
            $appointmentData = [
                'visitorName' => $this->name,
                'company'     => $this->company,
                'phone'       => $this->phone,
                'picName'     => $this->selected_pic_name,
                'department'  => $appointment->pic?->department?->name ?? '-',
                'visit_date'  => \Carbon\Carbon::parse($appointment->visit_date)->translatedFormat('d F Y'),
                'visit_time'  => $appointment->visit_time,
                'purpose'     => $appointment->purpose,
                'type'        => $appointment->type,
                'token'       => $appointment->token, // Ditambahkan untuk QR Code
            ];
            $this->dispatch('appointment-success', appt: $appointmentData);
        }

        $this->reset(['name', 'company', 'phone', 'purpose', 'pax', 'department_id', 'search_pic', 'selected_pic_id', 'selected_pic_name', 'step', 'pic_results', 'visit_type', 'visit_date', 'visit_time', 'is_verified_returning', 'verified_visitor_id']);
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $val) {
            $diff = $val - ($b[$i] ?? 0);
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    public function render()
    {
        return view('livewire.kiosk-walkin-form', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }
}
