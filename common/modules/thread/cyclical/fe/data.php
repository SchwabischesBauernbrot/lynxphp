<?php

return array(
  'thread_cyclical' => array(
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/:uri/thread/:threadNum/cyclical',
        'handler' => 'cyclic',
        /*
        'cacheSettings' => array(
          'files' => array(
            // theme is also would affect this caching
            'templates/header.tmpl', // wrapContent
            '../common/modules/board/banners/fe/views/banner_listing.tmpl', // homepage
            'templates/footer.tmpl', // wrapContent
          ),
        ),
        */
      ),
      array(
        'method'  => 'GET',
        'route'   => '/:uri/thread/:threadNum/uncyclic',
        'handler' => 'uncyclic',
      ),
    ),
    'forms' => array(
      /*
      array(
        'route' => '/user/settings',
        'handler' => 'user_settings',
      ),
      */
    ),
    'modules' => array(
      // add lock to post actions
      array(
        'pipeline' => 'PIPELINE_THREAD_ACTIONS',
        'module'   => 'add_cyclical_action',
      ),
      // add lock to thread icons
      array(
        'pipeline' => 'PIPELINE_THREAD_ICONS',
        'module'   => 'add_cyclical_icon',
      ),
    ),
    /*
    'pipelines' => array(
      array(
        // change the actual fields for settings
        'name' => 'PIPELINE_MODULE_USER_SETTINGS_FIELDS',
      ),
    ),
    */
  ),
);

?>