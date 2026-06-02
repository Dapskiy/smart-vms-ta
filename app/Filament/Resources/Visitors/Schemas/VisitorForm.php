<?php

namespace App\Filament\Resources\Visitors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VisitorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),

                TextInput::make('identity_number')
                    ->label('Nomor Identitas')
                    ->maxLength(255),

                Select::make('identity_type')
                    ->label('Jenis Identitas')
                    ->options([
                        'KTP'      => 'KTP',
                        'SIM'      => 'SIM',
                        'Passport' => 'Passport',
                    ])
                    ->native(false),

                TextInput::make('company')
                    ->label('Instansi / Perusahaan')
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(30),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),

                Toggle::make('is_blacklisted')
                    ->label('Blacklist?')
                    ->default(false)
                    ->columnSpanFull(),

                TextInput::make('blacklist_reason')
                    ->label('Alasan Blacklist')
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->visible(fn ($get) => $get('is_blacklisted')),
            ]);
    }
}
