<?php

namespace App\Observers;

use App\Models\AccessToken;

class AccessTokenObserver
{

    public function saving(AccessToken $token)
    {
        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        $comp = company();
        if ($comp) {
            $token->company_id = $comp->id;
        }
    }

}
