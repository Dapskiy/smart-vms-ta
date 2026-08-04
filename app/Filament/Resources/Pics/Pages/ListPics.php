<?php

namespace App\Filament\Resources\Pics\Pages;

use App\Filament\Resources\Pics\PicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPics extends ListRecords
{
    protected static string $resource = PicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    #[\Livewire\Attributes\On('save-pic-face')]
    public function savePicFace($picId, $descriptor, $photoBase64)
    {
        $pic = \App\Models\Pic::find($picId);
        if ($pic) {
            $pic->face_photo = [$photoBase64];
            $pic->face_features = [$descriptor];
            $pic->save();
            
            \Filament\Notifications\Notification::make()->title('Wajah Berhasil Disimpan')->success()->send();
        }
    }
}
