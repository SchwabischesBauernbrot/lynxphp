<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/admin/post_queue',
        'handler' => 'admin_queue',
        'portals' => array('admin' => array()),
      ),
      /*
      // these aren't cacheable period.
      array(
        'method'  => 'GET',
        'route'   => '/:uri/moderate',
        'handler' => 'community_moderate',
      ),
      array(
        'method'  => 'GET',
        'route'   => '/:uri/moderate/:id/allow',
        'handler' => 'community_moderate_allow',
      ),
      array(
        'method'  => 'GET',
        'route'   => '/:uri/moderate/:id/deny',
        'handler' => 'community_moderate_deny',
      ),
      */
      /*
      array(
        'method'  => 'GET',
        'route'   => '/:uri/settings/queueing.html',
        'handler' => 'setting',
        'cacheSettings' => array(
          'files' => array(
            // theme is also would affect this caching
            'templates/header.tmpl', // wrapContent
            //'../common/modules/board/banners/fe/views/banner_listing.tmpl', // homepage
            'templates/footer.tmpl', // wrapContent
          ),
        ),
      ),
      */
    ),
    'forms' => array(
      // these aren't cacheable period.
      array(
        'route' => '/:uri/settings/queueing',
        'handler' => 'board_setting',
      ),
      array(
        'route' => '/:uri/moderate',
        'handler' => 'community_moderate',
      ),
      array(
        'route' => '/admin/queue/:id/delete',
        'handler' => 'admin_delete',
      ),
      array(
        'route'   => '/admin/queue/strings',
        'handler' => 'admin_strings',
      ),
    ),
    'modules' => array(
      // add post queuing to boards settings
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_NAV',
        'module' => 'setting_nav',
      ),
      // code is disabled
      /*
      // allow BOs to control this
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_GENERAL',
        'module' => 'board_settings',
      ),
      */
      // add [Moderate] to board naviagtion
      array(
        'pipeline' => 'PIPELINE_BOARD_NAV',
        'module' => 'nav',
      ),
      // add queues to admin navigation
      array(
        'pipeline' => 'PIPELINE_ADMIN_NAV',
        'module' => 'nav_admin',
      ),
    ),
    'pipelines' => array(
      array('name' => 'PIPELINE_FE_ADMIN_QUEUE_ROW'),
    ),
  ),
);
return $fePkgs;

?>
