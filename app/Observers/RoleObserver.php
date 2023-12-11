<?php

namespace App\Observers;

use App\Models\Role;

class RoleObserver
{

    public function saving(Role $role)
    {
        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        $comp = company();
        if ($comp) {
            $role->company_id = $comp->id;
        }
    }

}
