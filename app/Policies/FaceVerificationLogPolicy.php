<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FaceVerificationLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class FaceVerificationLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FaceVerificationLog');
    }

    public function view(AuthUser $authUser, FaceVerificationLog $faceVerificationLog): bool
    {
        return $authUser->can('View:FaceVerificationLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FaceVerificationLog');
    }

    public function update(AuthUser $authUser, FaceVerificationLog $faceVerificationLog): bool
    {
        return $authUser->can('Update:FaceVerificationLog');
    }

    public function delete(AuthUser $authUser, FaceVerificationLog $faceVerificationLog): bool
    {
        return $authUser->can('Delete:FaceVerificationLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FaceVerificationLog');
    }

    public function restore(AuthUser $authUser, FaceVerificationLog $faceVerificationLog): bool
    {
        return $authUser->can('Restore:FaceVerificationLog');
    }

    public function forceDelete(AuthUser $authUser, FaceVerificationLog $faceVerificationLog): bool
    {
        return $authUser->can('ForceDelete:FaceVerificationLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FaceVerificationLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FaceVerificationLog');
    }

    public function replicate(AuthUser $authUser, FaceVerificationLog $faceVerificationLog): bool
    {
        return $authUser->can('Replicate:FaceVerificationLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FaceVerificationLog');
    }

}