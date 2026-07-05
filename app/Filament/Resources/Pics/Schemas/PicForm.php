<?php

namespace App\Filament\Resources\Pics\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),

                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),

                TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),

                Select::make('user_id')
                    ->label('Akun Login')
                    ->options(function ($record) {
                        // Show users that are not linked to any PIC, plus the currently assigned user
                        $query = User::whereDoesntHave('pic');
                        if ($record && $record->user_id) {
                            $query = User::where(function ($q) use ($record) {
                                $q->whereDoesntHave('pic')
                                  ->orWhere('id', $record->user_id);
                            });
                        }
                        return $query->pluck('name', 'id');
                    })
                    ->searchable()
                    ->placeholder('-- Pilih akun user (opsional) --')
                    ->helperText('Hubungkan PIC ke akun user agar bisa login ke admin panel.'),

                Toggle::make('is_available')
                    ->label('Tersedia?')
                    ->default(true),
            ]);
    }
}

