<?php

// post/actions

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
        'portals' => array('boardSettings' => array(
          'paramsCode' => array('uri' => array('type' => 'params', 'name' => 'uri'))
        )),
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
      // add to board settings nav "reports"
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_NAV',
        'module' => 'nav_settings',
      ),
      // add to globals' nav "reports"
      array(
        'pipeline' => 'PIPELINE_GLOBALS_NAV',
        'module' => 'global_nav',
      ),
      // add checkbox to a list of posts
      array('pipeline' => 'PIPELINE_POST_META_PROCESS', 'module' => 'post_meta_check'),
      // label
      array('pipeline' => 'PIPELINE_POST_META_PROCESS', 'module' => 'post_meta_label',),
      // inject form tag / post_actions into post portal
      array('pipeline' => 'PIPELINE_PORTAL_POST_EXTENSION', 'module' => 'post_ext',)
    ),
    'pipelines' => array(
      array('name' =>'PIPELINE_FE_POST_ACTIONS_DELETE_ADDITIONS'),
      array('name' =>'PIPELINE_FE_POST_ACTIONS_REPORT_ADDITIONS'),
      array('name' =>'PIPELINE_FE_POST_ACTIONS_MEDIA_ADDITIONS'),
      array('name' =>'PIPELINE_FE_POST_ACTIONS_BAN_ADDITIONS'),
    ),
  ),
);
return $fePkgs;

?>
