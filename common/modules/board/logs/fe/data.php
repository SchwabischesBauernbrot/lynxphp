<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/:uri/logs.html',
        'handler' => 'public_list',
        // needs backend resources
        /*
        'cacheSettings' => array(
          'files' => array(
            // theme is also would affect this caching
            'templates/header.tmpl', // wrapContent
            '../common/modules/board/logs/fe/views/log_listing.tmpl', // homepage
            'templates/footer.tmpl', // wrapContent
          ),
        ),
        */
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
