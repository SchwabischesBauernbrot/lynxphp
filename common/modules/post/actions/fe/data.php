<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'method'  => 'POST',
        'route'   => '/forms/board/:uri/actions',
        'handler' => 'actions',
      ),
      array(
        'route'   => '/:uri/report/:id',
        'handler' => 'report_post',
        'loggedIn' => true,
      ),
      // maybe this should be a form...
      array(
        'route'   => '/:uri/settings/reports',
        'handler' => 'report_list',
        'loggedIn' => true,
      ),
      array(
        'method'  => 'POST',
        'route'   => '/:uri/settings/reports',
        'handler' => 'report_multiaction',
        'loggedIn' => true,
      ),
      array(
        'route'   => '/:uri/settings/reports/:id/close',
        'handler' => 'close_report',
        'loggedIn' => true,
      ),
      array(
        'route'   => '/:uri/settings/reports/:id/delete',
        'handler' => 'delete_report',
        'loggedIn' => true,
      ),
      array(
        'route'   => '/:uri/settings/reports/:id/banPoster',
        'handler' => 'ban_report',
        'loggedIn' => true,
      ),
      array(
        'route'   => '/:uri/settings/reports/:id/banReport',
        'handler' => 'ban_reporter',
        'loggedIn' => true,
      ),
      array(
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
      array(
        'pipeline' => 'PIPELINE_POST_META_PROCESS',
        'module' => 'post_meta_check',
      ),
      array(
        'pipeline' => 'PIPELINE_POST_META_PROCESS',
        'module' => 'post_meta_label',
      ),
    ),
  ),
);
return $fePkgs;

?>
