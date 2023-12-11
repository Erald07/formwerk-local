<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{

    public function saving(User $user)
    {
        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        $comp = company();
        if ($comp) {
            $user->company_id = $comp->id;
        }
    }

}
