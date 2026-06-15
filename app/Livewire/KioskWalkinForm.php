<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Department;
use App\Models\Pic;
use App\Models\Visitor;
use App\Models\Appointment;
use Illuminate\Support\Str;
use App\Services\VisitIdService;

class KioskWalkinForm extends Component
{
    // Form fields
    public $name = '';
    public $company = '';
    public $phone = '';
    public $purpose = '';
    public $pax = 1;
    
    // Selection fields
    public $department_id = null;
    public $search_pic = '';
    public $selected_pic_id = null;
    public $selected_pic_name = '';

    // UI state
    public $step = 1;
    public $pic_results = [];
    public $is_searching = false;
    public $search_status = '';

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
        $this->validate([
            'department_id' => 'required|exists:departments,id',
            'selected_pic_id' => 'required|exists:pics,id',
            'purpose' => 'required|string',
        ], [
            'selected_pic_id.required' => 'Pilih karyawan yang akan dituju dari hasil pencarian.'
        ]);

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
            return;
        }

        // 2. Buat Appointment Walk-in
        Appointment::create([
            'visit_id' => VisitIdService::generate(),
            'visitor_id' => $visitor->id,
            'pic_id' => $this->selected_pic_id,
            'type' => 'walk-in',
            'status' => 'active', // Langsung active karena walk-in
            'visit_date' => now()->toDateString(),
            'purpose' => $this->purpose,
            'pax' => $this->pax,
            'token' => Str::random(10),
            'check_in_time' => now(), // Karena walk-in, otomatis dianggap check-in
        ]);

        // 3. Dispatch event agar UI Modal berubah sukses
        $this->dispatch('walkin-success', visitorName: $this->name, picName: $this->selected_pic_name);
        
        $this->reset(['name', 'company', 'phone', 'purpose', 'pax', 'department_id', 'search_pic', 'selected_pic_id', 'selected_pic_name', 'step', 'pic_results']);
    }

    public function render()
    {
        return view('livewire.kiosk-walkin-form', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }
}
