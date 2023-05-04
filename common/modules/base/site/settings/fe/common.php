<?php

// this data and functions used for all frontend module php code

// function are automatically exported

function getAdminFields($section) {
  $fields = false;
  $adminSettings = getCompiledSettings('admin');
  //print_r($adminSettings);

  if (isset($adminSettings[$section])) {
    if (is_array($adminSettings[$section])) {
      $fields = $adminSettings[$section];
    } else {
    }
  } else {
    $fields = array(
      'fields' => array(
        'siteName' => array(
          'label' => 'Site Name',
          'type'  => 'text',
        ),
        'logo' => array(
          'label' => 'Site Logo',
          'type'  => 'image',
        ),
        /*
        'showSiteName' => array(
          'label' => 'Show Site Name',
          'type'  => 'checkbox',
          'default' => true,
        ),
        'showWelcome' => array(
          'label' => 'Show Welcome to SITE NAME',
          'type'  => 'checkbox',
          'default' => true,
        ),
        'slogan' => array(
          'label' => 'Site Slogan',
          'type'  => 'text',
        ),
        'showSlogan' => array(
          'label' => 'Show Slogan',
          'type'  => 'checkbox',
          'default' => true,
        ),
        'showLogo' => array(
          'label' => 'Show Logo',
          'type'  => 'checkbox',
          'default' => true,
        ),
        */
      ),
    );
  }
  return $fields;
}

// allow export of data as $common in your handlers and modules
// now set in module.php s

return;

?>