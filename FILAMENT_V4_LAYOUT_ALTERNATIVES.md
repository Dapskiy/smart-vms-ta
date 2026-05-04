# Filament v4 Layout Components - Referensi Lengkap

## 🔴 ROOT CAUSE: Group Dipindahkan

Di Filament v4, semua **Layout Components** dipindahkan dari `Filament\Forms\Components` ke **`Filament\Schemas\Components`**.

| Component | v3 Location | v4 Location | Status |
|-----------|------------|------------|--------|
| Group | `Filament\Forms\Components\Group` | `Filament\Schemas\Components\Group` | ✅ Moved |
| Section | `Filament\Forms\Components\Section` | `Filament\Schemas\Components\Section` | ✅ Moved |
| Fieldset | `Filament\Forms\Components\Fieldset` | `Filament\Schemas\Components\Fieldset` | ✅ Moved |
| Flex | `Filament\Forms\Components\Flex` | `Filament\Schemas\Components\Flex` | ✅ Moved |
| Tabs | `Filament\Forms\Components\Tabs` | `Filament\Schemas\Components\Tabs` | ✅ Moved |

---

## ✅ SOLUSI: Import Dari Schemas

### IMPORT YANG BENAR (Filament v4):
```php
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
```

---

## 📋 Contoh Penggunaan Setiap Layout Component

### 1. **Group** - Grouping Sederhana Dengan Grid
Best untuk: Mengelompokkan fields terkait dalam satu baris/grid

```php
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\TextInput;

Group::make([
    TextInput::make('v_prefix')->placeholder('H')->maxLength(2),
    TextInput::make('v_number')->placeholder('1234')->maxLength(4),
    TextInput::make('v_suffix')->placeholder('AB')->maxLength(3),
])
    ->columns(3)  // 3 kolom dalam satu baris
    ->columnSpanFull()  // Mengambil full width
    ->label('Plat Nomor Kendaraan'),
```

---

### 2. **Section** - Collapsible/Expandable Groups
Best untuk: Mengelompokkan fields dalam section yang bisa di-collapse

```php
use Filament\Schemas\Components\Section;

Section::make('Informasi Kendaraan')
    ->description('Masukkan detail kendaraan pengunjung')
    ->schema([
        TextInput::make('v_prefix')->placeholder('H'),
        TextInput::make('v_number')->placeholder('1234'),
        TextInput::make('v_suffix')->placeholder('AB'),
    ])
    ->columns(3)
    ->collapsed()  // Mulai dalam keadaan collapsed
    ->icon('heroicon-o-truck'),  // Tambah icon
```

---

### 3. **Fieldset** - HTML <fieldset> Style
Best untuk: Grouping dengan visual border dan legend

```php
use Filament\Schemas\Components\Fieldset;

Fieldset::make('Data Kendaraan')
    ->schema([
        TextInput::make('v_prefix')->placeholder('H'),
        TextInput::make('v_number')->placeholder('1234'),
        TextInput::make('v_suffix')->placeholder('AB'),
    ])
    ->columns(3),
```

---

### 4. **Flex** - Flexible Layout (Flexbox)
Best untuk: Layout yang responsif dan fleksibel

```php
use Filament\Schemas\Components\Flex;

Flex::make([
    TextInput::make('v_prefix')->placeholder('H')->grow(false),
    TextInput::make('v_number')->placeholder('1234')->grow(),
    TextInput::make('v_suffix')->placeholder('AB')->grow(false),
])
    ->gap('md')  // gap antar elements
    ->direction('row'),  // row atau column
```

---

### 5. **Tabs** - Multi-Tab Layout
Best untuk: Organize fields dalam multiple tabs

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

Tabs::make('Form Sections')
    ->tabs([
        Tab::make('Informasi Dasar')
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
            ]),
        
        Tab::make('Data Kendaraan')
            ->schema([
                TextInput::make('v_prefix'),
                TextInput::make('v_number'),
                TextInput::make('v_suffix'),
            ])
            ->columns(3),
    ]),
```

---

## 🎯 REKOMENDASI UNTUK KASUS PLAT NOMOR ANDA

### Opsi 1: **Group** (SIMPLEST - Recommended)
```php
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\TextInput;

Group::make([
    TextInput::make('v_prefix')
        ->placeholder('H')
        ->maxLength(2)
        ->extraAttributes(['class' => 'nopol-prefix']),
    TextInput::make('v_number')
        ->placeholder('1234')
        ->maxLength(4)
        ->extraAttributes(['class' => 'nopol-number']),
    TextInput::make('v_suffix')
        ->placeholder('AB')
        ->maxLength(3)
        ->extraAttributes(['class' => 'nopol-suffix']),
])
    ->columns(3)
    ->columnSpanFull()
    ->label('Plat Nomor Kendaraan'),
```
**Pros**: Sederhana, fleksibel, responsive  
**Cons**: Tidak ada visual border/legend

---

### Opsi 2: **Fieldset** (VISUAL - Better UX)
```php
use Filament\Schemas\Components\Fieldset;

Fieldset::make('Plat Nomor Kendaraan')
    ->schema([
        TextInput::make('v_prefix')->placeholder('H')->maxLength(2),
        TextInput::make('v_number')->placeholder('1234')->maxLength(4),
        TextInput::make('v_suffix')->placeholder('AB')->maxLength(3),
    ])
    ->columns(3)
    ->columnSpanFull(),
```
**Pros**: Ada visual border dengan legend, user-friendly  
**Cons**: Tidak bisa di-collapse

---

### Opsi 3: **Section** (ADVANCED - Collapsible)
```php
use Filament\Schemas\Components\Section;

Section::make('Plat Nomor Kendaraan')
    ->schema([
        TextInput::make('v_prefix')->placeholder('H')->maxLength(2),
        TextInput::make('v_number')->placeholder('1234')->maxLength(4),
        TextInput::make('v_suffix')->placeholder('AB')->maxLength(3),
    ])
    ->columns(3)
    ->columnSpanFull()
    ->collapsed(false)  // Set to true jika mau collapsible
    ->icon('heroicon-o-document'),
```
**Pros**: Professional look, bisa di-collapse, ada icon  
**Cons**: Lebih kompleks, bisa take up more vertical space

---

## 📝 FULL CORRECTED FILE EXAMPLE

```php
<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Group;  // ✅ CORRECT IMPORT
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ... other components ...
                
                // ✅ CORRECT USAGE
                Group::make([
                    TextInput::make('v_prefix')->placeholder('H')->maxLength(2),
                    TextInput::make('v_number')->placeholder('1234')->maxLength(4),
                    TextInput::make('v_suffix')->placeholder('AB')->maxLength(3),
                ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->label('Plat Nomor Kendaraan'),
                
                // Hidden fields untuk status logic
                Hidden::make('vehicle_number')
                    ->dehydrated(true)
                    ->dehydrateStateUsing(fn($get) => strtoupper(trim("{$get('v_prefix')} {$get('v_number')} {$get('v_suffix')}"))),

                Hidden::make('type')
                    ->default(fn() => request()->query('type') === 'walk-in' ? 'walk-in' : 'appointment'),

                Hidden::make('status')
                    ->default(fn() => request()->query('type') === 'walk-in' ? 'active' : 'pending'),

                Hidden::make('token')
                    ->default(fn() => Str::random(10)),
            ]);
    }
}
```

---

## 🔍 DEBUGGING TIPS

### Verify Classes Exist:
```bash
php -r "require 'vendor/autoload.php'; echo (class_exists('Filament\Schemas\Components\Group') ? 'YES' : 'NO');"
```

### Clear Caches:
```bash
php artisan optimize:clear
php artisan filament:cache-commands
```

### Check Filament Version:
```bash
composer show filament/schemas
```

---

## ✅ LOGIC PRESERVATION

Dengan menggunakan `Group`, `Section`, atau `Fieldset` dari `Filament\Schemas\Components`, 
logic berikut tetap terjaga:

1. ✅ **Walk-in otomatis menjadi 'active'**: 
   ```php
   Hidden::make('status')
       ->default(fn() => request()->query('type') === 'walk-in' ? 'active' : 'pending')
   ```

2. ✅ **Appointment menjadi 'pending'**: 
   ```php
   Hidden::make('status')
       ->default(fn() => request()->query('type') === 'walk-in' ? 'active' : 'pending')
   ```

3. ✅ **Vehicle number concatenation**: 
   ```php
   Hidden::make('vehicle_number')
       ->dehydrated(true)
       ->dehydrateStateUsing(fn($get) => strtoupper(trim("{$get('v_prefix')} {$get('v_number')} {$get('v_suffix')}")))
   ```

4. ✅ **Type differentiation**: 
   ```php
   Hidden::make('type')
       ->default(fn() => request()->query('type') === 'walk-in' ? 'walk-in' : 'appointment')
   ```

---

## 📚 REFERENSI FILAMENT V4

- [Filament Schemas Documentation](https://filamentphp.com/docs/4.0/forms)
- [GitHub - Filament Schemas Package](https://github.com/filamentphp/filament/tree/4.x/packages/schemas)
- [Migration Guide: v3 to v4](https://filamentphp.com/docs/4.0/upgrade-guide)
