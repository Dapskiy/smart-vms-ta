<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KioskLocation;
use Illuminate\Auth\Access\HandlesAuthorization;

class KioskLocationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KioskLocation');
    }

    public function view(AuthUser $authUser, KioskLocation $kioskLocation): bool
    {
        return $authUser->can('View:KioskLocation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KioskLocation');
    }

    public function update(AuthUser $authUser, KioskLocation $kioskLocation): bool
    {
        return $authUser->can('Update:KioskLocation');
    }

    public function delete(AuthUser $authUser, KioskLocation $kioskLocation): bool
    {
        return $authUser->can('Delete:KioskLocation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:KioskLocation');
    }

    public function restore(AuthUser $authUser, KioskLocation $kioskLocation): bool
    {
        return $authUser->can('Restore:KioskLocation');
    }

    public function forceDelete(AuthUser $authUser, KioskLocation $kioskLocation): bool
    {
        return $authUser->can('ForceDelete:KioskLocation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KioskLocation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KioskLocation');
    }

    public function replicate(AuthUser $authUser, KioskLocation $kioskLocation): bool
    {
        return $authUser->can('Replicate:KioskLocation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:KioskLocation');
    }

}