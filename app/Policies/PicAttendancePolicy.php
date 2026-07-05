<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PicAttendance;
use Illuminate\Auth\Access\HandlesAuthorization;

class PicAttendancePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PicAttendance') || $authUser->pic()->exists();
    }

    public function view(AuthUser $authUser, PicAttendance $picAttendance): bool
    {
        if ($authUser->pic()->exists() && $authUser->pic->id === $picAttendance->pic_id) {
            return true;
        }

        return $authUser->can('View:PicAttendance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PicAttendance') || $authUser->pic()->exists();
    }

    public function update(AuthUser $authUser, PicAttendance $picAttendance): bool
    {
        return $authUser->can('Update:PicAttendance');
    }

    public function delete(AuthUser $authUser, PicAttendance $picAttendance): bool
    {
        return $authUser->can('Delete:PicAttendance');
    }
}
