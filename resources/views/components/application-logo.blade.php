
  <?php 
      $company = company();
      if($company && isset($company->company_logo)) {
        echo '<img width="100px" src="'.$company->company_logo.'" alt="'.$company->company_name.'" />';
      } else {
          echo '<h2 class="mb-0">FormWerk</h2>';
      }

  ?>

