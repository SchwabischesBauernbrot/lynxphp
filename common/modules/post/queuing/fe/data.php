<?php

$fePkgs = array(
  array(
    'handlers' => array(
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
      /*
      array(
        'route' => '/:uri/settings/queueing',
        'handler' => 'board_setting',
      ),
      */
      array(
        'route' => '/:uri/moderate',
        'handler' => 'community_moderate',
      ),
    ),
    'modules' => array(
      /*
      // add [Banner] to board naviagtion
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_NAV',
        'module' => 'setting_nav',
      ),
      */
      // allow BOs to control this
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_GENERAL',
        'module' => 'board_settings',
      ),
      // add [Moderate] to board naviagtion
      array(
        'pipeline' => 'PIPELINE_BOARD_NAV',
        'module' => 'nav',
      ),
    ),
  ),
);
return $fePkgs;

?>
