<?php

namespace App\Observers;

use App\Models\Record;

class RecordObserver
{

  public function saving(Record $record)
  {
    // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
    $comp = company();
    if ($comp) {
      $record->company_id = $comp->id;
    }
  }
}
