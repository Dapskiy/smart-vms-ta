<?php

namespace App\Rbac\Interfaces;

/**
 * Interface RbacModelInterface
 *
 * @package App\Rbac\Interfaces
 *
 * @author Andrey Girnik <girnikandrey@gmail.com>
 */
interface RbacModelInterface
{
    /**
     * Get Author id which related with user model record.
     * @return int
     */
    public function getAuthorIdAttribute(): int;
}
