<?php

namespace App\Observers;

use App\Models\Webfont;

class WebFontObserver
{

  public function saving(Webfont $webfont)
  {
    // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
    $comp = company();
    if ($comp) {
      $webfont->company_id = $comp->id;
    }
  }
}
