# 📏 Rules — Coding Convention & Contributor Guide

## Smart VMS — Development Rules

**Last Updated:** 2026-07-23

---

## 1. Coding Convention

### 1.1 PHP / Laravel

| Rule | Standard | Contoh |
|------|---------|--------|
| **Code Style** | PSR-12 (enforced via Laravel Pint) | `composer pint` |
| **Class Naming** | PascalCase | `AppointmentApprovalController` |
| **Method Naming** | camelCase | `getRemainingVisitorsAttribute()` |
| **Variable Naming** | camelCase | `$activeAppointments`, `$todayCount` |
| **Property Naming** | snake_case (Eloquent) | `$fillable`, `$guarded`, `$casts` |
| **Constant Naming** | UPPER_SNAKE_CASE | `Grid::INIT_ROWS_PER_PAGE` |
| **Database Column** | snake_case | `visitor_id`, `checkin_time`, `is_available` |
| **Migration Naming** | `YYYY_MM_DD_HHMMSS_description` | `2026_07_01_120000_add_walkin_approval_fields` |
| **Route Naming** | dot-separated, lowercase | `kiosk.face.checkin`, `admin.ai.chat` |

### 1.2 Blade / Frontend

| Rule | Standard | Contoh |
|------|---------|--------|
| **View File Naming** | kebab-case | `kiosk-lobby.blade.php` |
| **Component Naming** | kebab-case (Blade) | `<x-filament::button>` |
| **CSS Framework** | TailwindCSS v4 | Utility-first classes |
| **JavaScript** | ES6+ modules, no jQuery | Arrow functions, `const`/`let` |
| **Indentation** | 4 spaces (PHP), 2 spaces (Blade/JS) | Consistent within files |

### 1.3 File Organization

```
app/
├── Models/          → 1 model per file, nama = singular (Visitor, Appointment)
├── Services/        → Business logic layer, nama = <Domain>Service
├── Http/Controllers/
│   ├── Admin/       → Controllers yang butuh auth admin
│   └── Guest/       → Controllers untuk public/kiosk routes
├── Livewire/        → Livewire components
│   └── Kiosk/       → Kiosk-specific components
├── Policies/        → 1 policy per model, nama = <Model>Policy
├── Mail/            → Mailable classes
└── Filament/
    ├── Resources/   → 1 resource per model
    ├── Widgets/     → Dashboard widgets
    ├── Pages/       → Custom Filament pages
    └── Exports/     → Excel export classes
```

---

## 2. Style Guide

### 2.1 Model Rules

```php
// ✅ BENAR — Gunakan $guarded untuk model dengan banyak kolom
protected $guarded = ['id'];

// ✅ BENAR — Gunakan $fillable untuk model dengan sedikit kolom (eksplisit)
protected $fillable = ['name', 'email', 'phone'];

// ❌ SALAH — Jangan gunakan $guarded = [] kecuali untuk model yang benar-benar butuh mass-assign semua kolom
protected $guarded = [];
```

```php
// ✅ BENAR — Definisikan relasi dengan return type
public function appointments(): HasMany
{
    return $this->hasMany(Appointment::class);
}

// ✅ BENAR — Definisikan casts sebagai property atau method
protected $casts = [
    'visit_date'  => 'date',
    'is_available' => 'boolean',
];
```

### 2.2 Controller Rules

```php
// ✅ BENAR — Controller hanya handle HTTP concern (request/response)
// Business logic harus di Service class
public function ask(Request $request)
{
    $context = $this->aiService->buildContext();
    $data = $this->aiService->getDataForAI($request->message);
    // ... call OpenAI, return response
}

// ❌ SALAH — Business logic di controller
public function ask(Request $request)
{
    $appointments = Appointment::where('status', 'active')->get();
    // ... 100 baris logic ...
}
```

### 2.3 Migration Rules

```php
// ✅ BENAR — Selalu definisikan down() yang bisa rollback
public function up(): void
{
    Schema::table('appointments', function (Blueprint $table) {
        $table->string('checkout_method')->nullable();
    });
}

public function down(): void
{
    Schema::table('appointments', function (Blueprint $table) {
        $table->dropColumn('checkout_method');
    });
}

// ✅ BENAR — Gunakan hasColumn check untuk migration yang mungkin dijalankan berkali-kali
if (!Schema::hasColumn('visitors', 'face_photo')) {
    $table->longText('face_photo')->nullable();
}

// ✅ BENAR — Tambahkan comment di migration untuk enum values
$table->string('checkout_method')->nullable()
      ->comment('self | system | manual');
```

### 2.4 Filament Resource Rules

- Setiap Resource harus punya Policy yang terintegrasi dengan Shield
- Gunakan `->searchable()` pada kolom yang sering dicari
- Gunakan `->sortable()` pada kolom yang sering di-sort
- Gunakan `->toggleable()` untuk kolom yang optional di tampilan tabel
- Export Excel menggunakan `pxlrbt/filament-excel`

### 2.5 Livewire Component Rules

```php
// ✅ BENAR — Reset state dengan benar setelah form submit
public function resetForm()
{
    $this->reset(['name', 'company', 'phone', 'purpose']);
    $this->resetValidation();
    $this->resetErrorBag();
}

// ✅ BENAR — Gunakan Livewire events untuk komunikasi antar-component
#[On('resetWalkinForm')]
public function resetForm()
{
    // ...
}
```

---

## 3. Batasan untuk AI & Kontributor

### 3.1 Do's (Boleh Dilakukan) ✅

| Area | Rule |
|------|------|
| **Model** | Tambahkan accessor/mutator untuk computed attributes |
| **Migration** | Buat migration baru untuk perubahan schema — **JANGAN** edit migration lama |
| **Filament Resource** | Tambahkan filter, action, bulk action sesuai kebutuhan |
| **Livewire** | Buat component baru di `app/Livewire/` — satu component per file |
| **Service** | Tambahkan method baru di existing service atau buat service baru |
| **Policy** | Update policy jika ada fitur baru yang perlu authorization |
| **Seeder** | Tambahkan seeder baru — panggil dari `DatabaseSeeder.php` |
| **View** | Buat Blade component untuk UI yang reusable |
| **Comment** | Tulis komentar dalam **Bahasa Indonesia** untuk konsistensi dengan codebase |
| **Test** | Tambahkan PHPUnit test di `tests/` |

### 3.2 Don'ts (Jangan Dilakukan) ❌

| Area | Rule | Alasan |
|------|------|--------|
| **Migration** | ❌ Jangan edit/hapus migration yang sudah di-commit | Bisa break database state di environment lain |
| **Encryption** | ❌ Jangan ubah format enkripsi face_photo/face_features tanpa migration data | Data lama tidak bisa dibaca |
| **Eloquent Cast** | ❌ Jangan tambahkan face_photo/face_features ke `$casts` | Konflik dengan custom accessor — menyebabkan double-decode |
| **ENV** | ❌ Jangan hardcode secrets (API key, password) di source code | Gunakan `.env` dan `config()` |
| **Composer** | ❌ Jangan tambahkan package tanpa diskusi | Setiap dependency = maintenance burden |
| **Database** | ❌ Jangan gunakan raw SQL kecuali benar-benar diperlukan | Gunakan Eloquent/Query Builder |
| **Gate bypass** | ❌ Jangan hapus `Gate::before` untuk super_admin | Core security feature |
| **Kiosk routes** | ❌ Jangan tambahkan auth middleware ke kiosk routes (`/kiosk/*`) | Kiosk harus bisa diakses tanpa login |
| **File upload** | ❌ Jangan simpan file di `public/` — gunakan `storage/app/private/` | Data sensitif harus private |

### 3.3 Batasan Khusus untuk AI Code Assistant

> Berikut aturan tambahan yang **wajib dipatuhi oleh AI coding assistant** (Copilot, Cursor, Gemini, Claude, dll.) saat memodifikasi codebase ini:

#### 3.3.1 Context Rules

- **Baca file terkait** sebelum mengubah kode — pahami relasi antar-model, controller, dan service
- **Cek migration history** sebelum membuat migration baru — hindari duplikasi kolom
- **Perhatikan existing patterns** — ikuti cara yang sudah ada (e.g., face photo encryption pattern)

#### 3.3.2 Modification Rules

| Rule | Detail |
|------|--------|
| Jangan rename kolom DB | Bisa break query di seluruh codebase |
| Jangan ubah Appointment status enum | Status machine sudah terdefinisi dan digunakan di banyak tempat |
| Jangan hapus komentar yang ada | Komentar dokumentasi = bagian dari deliverable TA |
| Jangan ubah `VisitIdService` format | Format `VST-YYYYMMDD-XXXX` sudah digunakan di existing data |
| Jangan refactor file Blade kiosk | File besar (120KB+) tapi sudah terintegrasi — refactor berisiko tinggi |
| Preservasi backward compatibility | Accessor harus tetap bisa membaca data lama (plain JSON, double-encrypted, dll.) |

#### 3.3.3 Security Rules

| Rule | Detail |
|------|--------|
| Selalu enkripsi data biometrik | `face_photo` dan `face_features` harus **SELALU** encrypted at-rest |
| Gunakan `Crypt` facade | Bukan `encrypt()`/`decrypt()` global — gunakan `Crypt::encrypt()` / `Crypt::encryptString()` |
| Validasi input | Semua input dari kiosk harus divalidasi — kiosk adalah surface yang public |
| Jangan expose face descriptor | Face features (128-dim array) tidak boleh dikembalikan ke response JSON |
| Sanitize AI prompt | Pastikan user input ke AI chatbot tidak bisa melakukan prompt injection terhadap system prompt |

#### 3.3.4 Testing Rules

```bash
# Sebelum push, pastikan:
php artisan test          # Semua test pass
php artisan pint          # Code style clean
php artisan migrate:fresh --seed  # Migration bisa dijalankan dari nol
```

---

## 4. Git Convention

### 4.1 Branch Naming

| Pattern | Contoh | Penggunaan |
|---------|--------|-----------|
| `feature/<nama>` | `feature/pic-attendance` | Fitur baru |
| `fix/<nama>` | `fix/face-photo-encryption` | Bug fix |
| `refactor/<nama>` | `refactor/kiosk-components` | Refactoring |
| `docs/<nama>` | `docs/add-prd` | Dokumentasi |

### 4.2 Commit Message

Format: `<type>: <description>`

| Type | Penggunaan |
|------|-----------|
| `feat` | Fitur baru |
| `fix` | Bug fix |
| `refactor` | Refactoring tanpa ubah behavior |
| `docs` | Perubahan dokumentasi |
| `style` | Formatting, whitespace (no logic change) |
| `test` | Tambah/update test |
| `chore` | Update dependency, config, dll. |

```
feat: add face verification logging
fix: handle double-encrypted face photo in accessor
refactor: extract AI intent handlers to AdminAIService
docs: add PRD and architecture documentation
```

---

## 5. Environment & Deployment

### 5.1 Development Setup

```bash
# 1. Clone & install
composer install
npm install
cp .env.example .env
php artisan key:generate

# 2. Database
# Pastikan PostgreSQL sudah running
php artisan migrate --seed

# 3. Run development server (all-in-one)
composer dev
# Menjalankan: server + queue + pail (log) + vite secara concurrent
```

### 5.2 Environment Variables yang Wajib

| Variable | Contoh | Keterangan |
|----------|--------|-----------|
| `DB_CONNECTION` | `pgsql` | **Harus PostgreSQL** |
| `DB_DATABASE` | `smart-vms` | Nama database |
| `OPENAI_API_KEY` | `sk-proj-...` | API key untuk AI chatbot |
| `OPENAI_MODEL` | `gpt-4o-mini` | Model OpenAI |
| `KIOSK_PIN` | `654321` | PIN akses kiosk |
| `KIOSK_ALLOWED_IPS` | `127.0.0.1` | IP yang diizinkan (gunakan `*` untuk semua) |
| `QUEUE_CONNECTION` | `database` | Queue driver |
| `MAIL_MAILER` | `smtp` / `log` | Mail driver (gunakan `log` untuk dev) |

### 5.3 Perintah Artisan Berguna

```bash
# Shield: generate permission & policy untuk semua resource
php artisan shield:generate --all

# Shield: create super_admin
php artisan shield:super-admin

# Pint: format code
./vendor/bin/pint

# Fresh migrate + seed
php artisan migrate:fresh --seed

# Clear all cache
php artisan optimize:clear
```
