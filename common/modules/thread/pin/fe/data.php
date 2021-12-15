<?php

return array(
  'thread_pin' => array(
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/:uri/thread/:threadNum/pin',
        'handler' => 'pin',
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
        'route'   => '/:uri/thread/:threadNum/unpin',
        'handler' => 'unpin',
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
      // add pin to post actions
      array(
        'pipeline' => 'PIPELINE_THREAD_ACTIONS',
        'module'   => 'add_pin_action',
      ),
      // add pin to thread icons
      array(
        'pipeline' => 'PIPELINE_THREAD_ICONS',
        'module'   => 'add_pin_icon',
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