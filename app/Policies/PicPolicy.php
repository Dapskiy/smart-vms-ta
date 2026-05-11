<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Pic;
use Illuminate\Auth\Access\HandlesAuthorization;

class PicPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pic');
    }

    public function view(AuthUser $authUser, Pic $pic): bool
    {
        return $authUser->can('View:Pic');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pic');
    }

    public function update(AuthUser $authUser, Pic $pic): bool
    {
        return $authUser->can('Update:Pic');
    }

    public function delete(AuthUser $authUser, Pic $pic): bool
    {
        return $authUser->can('Delete:Pic');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Pic');
    }

    public function restore(AuthUser $authUser, Pic $pic): bool
    {
        return $authUser->can('Restore:Pic');
    }

    public function forceDelete(AuthUser $authUser, Pic $pic): bool
    {
        return $authUser->can('ForceDelete:Pic');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pic');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pic');
    }

    public function replicate(AuthUser $authUser, Pic $pic): bool
    {
        return $authUser->can('Replicate:Pic');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pic');
    }

}