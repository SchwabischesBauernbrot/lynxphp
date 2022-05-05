<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/:uri/board_settings.php',
        'handler' => 'landing',
      ),
      /*
      array(
        'method'  => 'GET',
        'route'   => '/:uri/logs',
        'handler' => 'public_list',
      ),
      */
    ),
    'forms' => array(
      array(
        'route' => '/:uri/settings/board',
        'handler' => 'board_settings',
      ),
    ),
    'modules' => array(
      /*
      array(
        'pipeline' => 'PIPELINE_BOARD_NAV',
        'module' => 'nav',
      ),
      */
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_NAV',
        'module' => 'nav_settings',
      ),
    ),
  ),
);
return $fePkgs;

?>
