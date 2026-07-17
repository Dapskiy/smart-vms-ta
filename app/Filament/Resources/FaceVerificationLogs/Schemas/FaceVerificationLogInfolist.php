<?php

namespace App\Filament\Resources\FaceVerificationLogs\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FaceVerificationLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('visitor_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('visitor_name')
                    ->placeholder('-'),
                TextEntry::make('type'),
                TextEntry::make('euclidean_distance')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('threshold')
                    ->numeric(),
                IconEntry::make('is_success')
                    ->boolean(),
                TextEntry::make('error_message')
                    ->placeholder('-'),
                TextEntry::make('ip_address')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
