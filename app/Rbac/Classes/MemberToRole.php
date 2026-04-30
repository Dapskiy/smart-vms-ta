<?php

namespace App\Rbac\Classes;

use App\Rbac\Interfaces\RbacUserInterface;
use App\Rbac\Models\Role;

/**
 * Class MemberToRole
 * @package App\Rbac\Classes
 */
class MemberToRole
{
    /**
     * @var RbacUserInterface
     */
    private $member;

    /**
     * @var Role
     */
    private $role;

    /**
     * @param RbacUserInterface $member
     * @param Role $role
     * @return static
     */
    public static function make(RbacUserInterface $member, Role $role)
    {
        $obj = new static();

        $obj->member = $member;
        $obj->role = $role;

        return $obj;
    }

    /**
     * @return RbacUserInterface
     */
    public function getMember(): RbacUserInterface
    {
        return $this->member;
    }

    /**
     * @return Role
     */
    public function getRole(): Role
    {
        return $this->role;
    }
}
