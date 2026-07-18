<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Pic;
use Filament\Notifications\Notification;

class TopbarAvailabilityToggle extends Component
{
    public bool $isAvailable = false;
    public bool $isPic = false;

    public function mount(): void
    {
        if (auth()->check()) {
            $pic = Pic::where('user_id', auth()->id())->first();
            if ($pic) {
                $this->isPic = true;
                $this->isAvailable = (bool) $pic->is_available;
            }
        }
    }

    public function toggle(): void
    {
        if (!auth()->check()) {
            return;
        }

        $pic = Pic::where('user_id', auth()->id())->first();
        if ($pic) {
            $this->isAvailable = !$this->isAvailable;
            $pic->update(['is_available' => $this->isAvailable]);

            $statusText = $this->isAvailable ? 'Tersedia' : 'Tidak Tersedia';
            $color = $this->isAvailable ? 'success' : 'danger';

            Notification::make()
                ->title("Status diubah ke {$statusText}")
                ->success()
                ->color($color)
                ->send();
        }
    }

    public function render()
    {
        if (!$this->isPic) {
            return <<<'BLADE'
            <div></div>
            BLADE;
        }

        return view('livewire.topbar-availability-toggle');
    }
}
