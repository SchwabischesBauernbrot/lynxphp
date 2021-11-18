<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/:uri/banners.html',
        'handler' => 'public_list',
        'cacheSettings' => array(
          'files' => array(
            // theme is also would affect this caching
            'templates/header.tmpl', // wrapContent
            '../common/modules/board/banners/fe/views/banner_listing.tmpl', // homepage
            'templates/footer.tmpl', // wrapContent
          ),
        ),
      ),
      array(
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
