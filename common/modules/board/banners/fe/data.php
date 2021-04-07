<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/:uri/banners',
        'handler' => 'public_list',
      ),
      array(
        'method'  => 'GET',
        'route'   => '/:uri/settings/banners',
        'handler' => 'settings_list',
      ),
    ),
    'forms' => array(
      array(
        'route' => '/:uri/settings/banners/add',
        'handler' => 'add',
      ),
      array(
        'route' => '/:uri/settings/banners/:id/delete',
        'handler' => 'delete',
      ),
    ),
    'modules' => array(
      // add [Banner] to board naviagtion
      array(
        'pipeline' => 'PIPELINE_BOARD_NAV',
        'module' => 'nav',
      ),
      // add {{banner}} tag to board_header_tmpl
      array(
        'pipeline' => 'PIPELINE_BOARD_HEADER_TMPL',
        'module' => 'banner',
      ),
      // add {{banner}} tag to board_details_tmpl
      /*
      array(
        'pipeline' => 'PIPELINE_BOARD_DETAILS_TMPL',
        'module' => 'banner',
      ),
      */
      // adds banners to nav settings
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_NAV',
        'module' => 'nav_settings',
      ),
    ),
  ),
);
return $fePkgs;

?>
