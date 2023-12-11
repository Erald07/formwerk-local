<?php

namespace App\Observers;

use App\Models\Setting;

class SettingObserver
{

    public function saving(Setting $setting)
    {
        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        $comp = company();
        if ($comp) {
            $setting->company_id = $comp->id;
        }
    }

}
