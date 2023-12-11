<?php

namespace App\Observers;

use App\Models\Style;

class StyleObserver
{

  public function saving(Style $style)
  {
    // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
    $comp = company();
    if ($comp) {
      $style->company_id = $comp->id;
    }
  }
}
