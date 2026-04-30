<?php

namespace App\Rbac;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use App\Rbac\Models\Permission;
use App\Rbac\Interfaces\{RbacUserInterface, RbacModelInterface};
use App\Rbac\Classes\MemberToRole;

/**
 * Class RbacAuthServiceProvider
 *
 * @package App\Rbac
 *
 * @author Andrey Girnik <girnikandrey@gmail.com>
 */
class RbacAuthServiceProvider extends AuthServiceProvider
{
    /**
     * Register any authentication / authorization services.
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        $this->registerRbacPolicies();
    }

    /**
     * Register Rbac policies.
     */
    public function registerRbacPolicies()
    {
        /*
        |--------------------------------------------------------------------------
        | ADMIN member section.
        |--------------------------------------------------------------------------
        */
        Gate::define(Permission::ADMINISTRATE_PERMISSION, function (RbacUserInterface $user) {
            return $user->hasAccess([
                Permission::ADMINISTRATE_PERMISSION
            ]);
        });

        Gate::define(Permission::ASSIGN_ROLE_FLAG, function (RbacUserInterface $user, MemberToRole $memberToRole) {
            return $user->canAssignRole($memberToRole->getMember(), $memberToRole->getRole());
        });

        Gate::define(Permission::DELETE_MEMBER_FLAG, function (RbacUserInterface $user, $memberKey) {
            return $user->getMemberKeyAttribute() != $memberKey;
        });


        /*
        |--------------------------------------------------------------------------
        | Record section for application.
        |--------------------------------------------------------------------------
        */
        Gate::define(Permission::VIEW_RECORD_PERMISSION, function (RbacUserInterface $user, RbacModelInterface $model = null) {
            return $user->hasAccess([
                Permission::VIEW_RECORD_PERMISSION
            ]) || !empty($model) ? $user->getMemberKeyAttribute() == $model->getAuthorIdAttribute() : false;
        });

        Gate::define(Permission::CREATE_RECORD_PERMISSION, function (RbacUserInterface $user) {
            return $user->hasAccess([
                Permission::CREATE_RECORD_PERMISSION
            ]);
        });

        Gate::define(Permission::UPDATE_RECORD_PERMISSION, function (RbacUserInterface $user, RbacModelInterface $model = null) {
            return $user->hasAccess([
                Permission::UPDATE_RECORD_PERMISSION
            ]) || !empty($model) ? $user->getMemberKeyAttribute() == $model->getAuthorIdAttribute() : false;
        });

        Gate::define(Permission::DELETE_RECORD_PERMISSION, function (RbacUserInterface $user, RbacModelInterface $model = null) {
            return $user->hasAccess([
                Permission::DELETE_RECORD_PERMISSION
            ]) || !empty($model) ? $user->getMemberKeyAttribute() == $model->getAuthorIdAttribute() : false;
        });

        Gate::define(Permission::PUBLISH_RECORD_PERMISSION, function (RbacUserInterface $user) {
            return $user->hasAccess([
                Permission::PUBLISH_RECORD_PERMISSION
            ]);
        });
    }
}
