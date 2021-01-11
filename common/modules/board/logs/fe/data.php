<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/:uri/logs',
        'handler' => 'public_list',
      ),
      /*
      array(
        'method'  => 'GET',
        'route'   => '/:uri/settings/logs',
        'handler' => 'settings_list',
      ),
      */
    ),
    'forms' => array(
      /*
      array(
        'route' => '/:uri/settings/logs/add',
        'handler' =. 'add',
      ),
      array(
        'route' => '/:uri/settings/logs/delete',
        'handler' =. 'delete',
      ),
      */
    ),
    'modules' => array(
      array(
        'pipeline' => 'PIPELINE_BOARD_NAV',
        'module' => 'nav',
      ),
      /*
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_NAV',
        'module' => 'nav_settings',
      ),
      */
    ),
  ),
);
return $fePkgs;

?>
