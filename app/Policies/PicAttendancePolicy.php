<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PicAttendance;
use Illuminate\Auth\Access\HandlesAuthorization;

class PicAttendancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PicAttendance');
    }

    public function view(AuthUser $authUser, PicAttendance $picAttendance): bool
    {
        return $authUser->can('View:PicAttendance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PicAttendance');
    }

    public function update(AuthUser $authUser, PicAttendance $picAttendance): bool
    {
        return $authUser->can('Update:PicAttendance');
    }

    public function delete(AuthUser $authUser, PicAttendance $picAttendance): bool
    {
        return $authUser->can('Delete:PicAttendance');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PicAttendance');
    }

    public function restore(AuthUser $authUser, PicAttendance $picAttendance): bool
    {
        return $authUser->can('Restore:PicAttendance');
    }

    public function forceDelete(AuthUser $authUser, PicAttendance $picAttendance): bool
    {
        return $authUser->can('ForceDelete:PicAttendance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PicAttendance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PicAttendance');
    }

    public function replicate(AuthUser $authUser, PicAttendance $picAttendance): bool
    {
        return $authUser->can('Replicate:PicAttendance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PicAttendance');
    }

}