<?php

namespace App\Observers;

use App\Models\Form;

class FormObserver
{

    public function saving(Form $form)
    {
        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        $comp = company();
        if ($comp) {
            $form->company_id = $comp->id;
        }
    }

}
