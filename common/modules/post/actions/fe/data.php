<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'method'  => 'POST',
        'route'   => '/forms/board/:uri/actions',
        'handler' => 'actions',
      ),
      // maybe this should be a form...
      array(
        'method'  => 'GET',
        'route'   => '/:uri/settings/reports',
        'handler' => 'report_list',
      ),
      array(
        'method'  => 'POST',
        'route'   => '/:uri/settings/reports',
        'handler' => 'report_multiaction',
      ),
      array(
        'method'  => 'GET',
        'route'   => '/:uri/settings/reports/:id/close',
        'handler' => 'close_report',
      ),
      array(
        'method'  => 'GET',
        'route'   => '/:uri/settings/reports/:id/delete',
        'handler' => 'delete_report',
      ),
      array(
        'method'  => 'GET',
        'route'   => '/:uri/settings/reports/:id/banPoster',
        'handler' => 'ban_report',
      ),
      array(
        'method'  => 'GET',
        'route'   => '/:uri/settings/reports/:id/banReport',
        'handler' => 'ban_reporter',
      ),
      array(
        'method'  => 'GET',
        'route'   => '/globals/reports',
        'handler' => 'global_report_list',
        'loggedIn' => true,
      ),
    ),
    'forms' => array(),
    'modules' => array(
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_NAV',
        'module' => 'nav_settings',
      ),
      array(
        'pipeline' => 'PIPELINE_GLOBALS_NAV',
        'module' => 'global_nav',
      ),
    ),
  ),
);
return $fePkgs;

?>
