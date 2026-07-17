<?php

namespace App\Filament\Resources\FaceVerificationLogs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaceVerificationLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('visitor_id')
                    ->numeric(),
                TextInput::make('visitor_name'),
                TextInput::make('type')
                    ->required(),
                TextInput::make('euclidean_distance')
                    ->numeric(),
                TextInput::make('threshold')
                    ->required()
                    ->numeric()
                    ->default(0.5),
                Toggle::make('is_success')
                    ->required(),
                TextInput::make('error_message'),
                TextInput::make('ip_address'),
            ]);
    }
}
