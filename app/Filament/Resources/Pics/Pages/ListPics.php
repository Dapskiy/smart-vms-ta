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
            $existingPhotos = is_array($pic->face_photo) ? $pic->face_photo : [];
            $existingFeatures = is_array($pic->face_features) ? $pic->face_features : [];
            
            if (count($existingPhotos) < 10) {
                $existingPhotos[] = $photoBase64;
                $pic->face_photo = $existingPhotos;
            }
            if (count($existingFeatures) < 10) {
                $existingFeatures[] = $descriptor;
                $pic->face_features = $existingFeatures;
            }
            $pic->save();
            
            \Filament\Notifications\Notification::make()->title('Wajah Berhasil Disimpan')->success()->send();
        }
    }
}
