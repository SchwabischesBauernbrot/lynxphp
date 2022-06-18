<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/:uri/thread/:threadId/:postId/react',
        'handler' => 'select_react',
      ),
      array(
        'route'   => '/:uri/thread/:threadId/:postId/react/:react',
        'handler' => 'add_react',
      ),
      array(
        'route'   => '/:uri/thread/:threadId/:postId/react/delete',
        'handler' => 'del_react',
      ),
      array(
        'route'   => '/:uri/settings/reacts.html',
        'handler' => 'list',
      ),
      /*
      array(
        'method'  => 'GET',
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
      */
    ),
    'forms' => array(
      array(
        'route' => '/:uri/settings/reacts/add',
        'handler' => 'add',
      ),
      array(
        'route' => '/:uri/settings/reacts/:id/delete',
        'handler' => 'delete',
      ),
    ),
    'modules' => array(
      // allow BOs to control this
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_GENERAL',
        'module' => 'board_settings_field',
      ),
      // add "reacts" to boards settings
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_NAV',
        'module' => 'board_settings_nav',
      ),
      // add "reacts" to post actions
      array(
        'pipeline' => 'PIPELINE_POST_ACTIONS',
        'module' => 'post_action',
      ),
      /*
      // add reacts to post icons
      array(
        'pipeline' => 'PIPELINE_POST_ICONS',
        'module' => 'post_icons',
      ),
      */
      // add reacts to post html
      array(
        'pipeline' => 'PIPELINE_POST_ROW_APPEND',
        'module' => 'post_append',
      ),
    ),
  ),
);
return $fePkgs;

?>
