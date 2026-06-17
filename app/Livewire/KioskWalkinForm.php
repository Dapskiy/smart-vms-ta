<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Department;
use App\Models\Pic;
use App\Models\Visitor;
use App\Models\Appointment;
use Illuminate\Support\Str;
use App\Services\VisitIdService;
use Livewire\Attributes\On;

class KioskWalkinForm extends Component
{
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
            $this->name = $bestMatch->name;
            $this->company = $bestMatch->company;
            $this->phone = $bestMatch->phone;
            $this->verified_visitor_id = $bestMatch->id;
            $this->is_verified_returning = true;
            
            $this->step = 2; // Lompat ke tujuan
            $this->dispatch('walkin-form-reopen'); // Buka modal kembali
            session()->flash('general_success', 'Selamat datang kembali, ' . $this->name . '!');
        } else {
            $this->step = 1; // Paksa jadi tamu baru
            $this->is_verified_returning = false;
            $this->dispatch('walkin-form-reopen');
            $this->addError('general', 'Wajah tidak dikenali. Silakan mendaftar sebagai tamu baru.');
        }
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
                ->where('is_available', true)
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
        $this->selected_pic_id = $picId;
        $this->selected_pic_name = $picName;
        $this->search_pic = '';
        $this->pic_results = [];
        $this->search_status = '';
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
                'phone' => 'required|string|max:20',
                'pax' => 'required|integer|min:1|max:50',
            ]);
            $this->step = 2;
        }
    }

    public function previousStep()
    {
        $this->step = 1;
    }

    public function submit()
    {
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
            // Berhenti di sini, trigger face scan JS di frontend (pendaftar baru)
            $this->dispatch('trigger-face-scan');
        }
    }

    #[On('finalizeWalkin')]
    public function finalizeWalkin($descriptor, $photoBase64)
    {
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

        if ($visitor->is_blacklisted) {
            $this->addError('general', 'Mohon maaf, Anda tidak dapat melanjutkan proses registrasi. Silakan hubungi Resepsionis.');
            $this->dispatch('walkin-error');
            return;
        }

        // 2. Global Face Duplicate Check (Anti-Spoofing / Anti-Duplicate)
        // Memastikan wajah pengunjung baru ini tidak pernah didaftarkan atas nama orang lain
        $allOtherVisitors = Visitor::whereNotNull('face_features')->where('id', '!=', $visitor->id)->get();
        $globalThreshold = 0.45; // Cukup ketat untuk deteksi duplikat

        foreach ($allOtherVisitors as $otherVisitor) {
            $otherStored = $otherVisitor->face_features ?? [];
            if (!is_array($otherStored)) continue;
            if (isset($otherStored[0]) && !is_array($otherStored[0])) $otherStored = [$otherStored];

            foreach ($otherStored as $otherDescriptor) {
                if ($this->euclideanDistance($otherDescriptor, $descriptor) <= $globalThreshold) {
                    $this->addError('general', 'Wajah Anda sudah terdaftar atas nama ' . $otherVisitor->name . '. Silakan gunakan opsi "Sudah Pernah Berkunjung".');
                    $this->dispatch('walkin-error'); 
                    return;
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
                 $this->addError('general', 'Wajah tidak cocok dengan data pendaftaran sebelumnya.');
                 $this->dispatch('walkin-error'); 
                 return;
            }
        }

        // 3. Simpan data wajah baru (append max 10)
        try {
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
        } catch (\Exception $e) {
            // Ignore error log
        }

        $this->createAppointmentRecord($visitor);
    }

    private function createAppointmentRecord(Visitor $visitor)
    {
        // 4. Buat Appointment
        $isWalkIn = $this->visit_type === 'walk-in';
        
        Appointment::create([
            'visit_id' => VisitIdService::generate(),
            'visitor_id' => $visitor->id,
            'pic_id' => $this->selected_pic_id,
            'type' => $this->visit_type,
            'status' => $isWalkIn ? 'active' : 'pending', 
            'visit_date' => $isWalkIn ? now()->toDateString() : $this->visit_date,
            'visit_time' => $isWalkIn ? now()->toTimeString() : $this->visit_time,
            'purpose' => $this->purpose,
            'pax' => $this->pax,
            'token' => Str::random(10),
            'check_in_time' => $isWalkIn ? now() : null,
        ]);

        // 5. Dispatch event agar UI Modal berubah sukses
        $appointmentData = [
            'visitorName' => $this->name,
            'picName' => $this->selected_pic_name,
            'visit_date' => \Carbon\Carbon::parse($appointment->visit_date)->translatedFormat('d F Y'),
            'visit_time' => $appointment->visit_time,
            'purpose' => $appointment->purpose,
            'type' => $appointment->type
        ];

        if ($isWalkIn) {
            $this->dispatch('walkin-success', appt: $appointmentData);
        } else {
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
