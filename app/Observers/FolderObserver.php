<?php

namespace App\Observers;

use App\Models\Folder;

class FolderObserver
{

    public function saving(Folder $folder)
    {
        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        $comp = company();
        if ($comp) {
            $folder->company_id = $comp->id;
        }
    }

}
