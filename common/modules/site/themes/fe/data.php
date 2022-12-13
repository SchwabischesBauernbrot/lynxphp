<?php

$fePkgs = array(
  array(
    // what was this for?
    //'dependencies' => array('user/mgmt'),
    'handlers' => array(
      // css passthru
      array(
        'method'  => 'GET',
        'route'   => '/user/settings/theme.php',
        'handler' => 'css',
        // set content-type?
        /*
        'cacheSettings' => array(
          'files' => array(
            //'../common/modules/site/themes/fe/shared.php',
            'css/themes/{{route.theme}}.css', // wrapContent
            'templates/thread_listing.tmpl', // demo/boards
            'templates/header.tmpl', // wrapContent
            'templates/footer.tmpl', // wrapContent
            'templates/mixins/board_header.tmpl',
            'templates/mixins/board_footer.tmpl',
            'templates/mixins/post_detail.tmpl', // renderPost
            'templates/mixins/post_actions.tmpl', // renderPostActions
          ),
        ),
        */
      ),
      array(
        'method'  => 'GET',
        'route'   => '/user/settings/themedemo/:theme.html',
        'handler' => 'demo',
        'cacheSettings' => array(
          'files' => array(
            //'../common/modules/site/themes/fe/shared.php',
            // route.theme is set from :theme
            'css/themes/{{route.theme}}.css', // wrapContent
            'templates/thread_listing.tmpl', // demo/boards
            'templates/header.tmpl', // wrapContent
            'templates/footer.tmpl', // wrapContent
            'templates/mixins/board_header.tmpl',
            'templates/mixins/board_footer.tmpl',
            'templates/mixins/post_detail.tmpl', // renderPost
            'templates/mixins/post_actions.tmpl', // renderPostActions
          ),
        ),
      ),
    ),
    'forms' => array(),
    'modules' => array(
      // add form control type
      array(
        'pipeline' => 'PIPELINE_FORM_WIDGET_THEMETHUMBNAILS',
        'module' => 'widget_themethumbnails',
      ),
      // add category/field to settings
      array(
        'pipeline' => 'PIPELINE_MODULE_USER_SETTINGS_FIELDS',
        'module' => 'user_settings_fields',
      ),
      // head tag
      array(
        'pipeline' => 'PIPELINE_SITE_HEAD',
        'module' => 'site_head',
      ),
      // head tag
      array(
        'pipeline' => 'PIPELINE_ADMIN_SETTING_GENERAL',
        'module' => 'admin_settings',
      ),
    ),
    'css' => array(
      // we just need to add them there to allow them all
      // which theme matters on user setting
      //array('file' => 'board_banner.css')
      array('file' => 'amoled.css'),
      array('file' => 'army-green.css'),
      array('file' => 'cancer.css'),
      array('file' => 'chaos.css'),
      array('file' => 'choc.css'),
      array('file' => 'clear.css'),
      array('file' => 'darkblue.css'),
      array('file' => 'gurochan.css'),
      array('file' => 'kc.css'),
      array('file' => 'lain.css'),
      array('file' => 'miku.css'),
      array('file' => 'mushroom.css'),
      array('file' => 'navy.css'),
      array('file' => 'pink.css'),
      array('file' => 'rei-zero.css'),
      array('file' => 'robot.css'),
      array('file' => 'seafoam-dark.css'),
      array('file' => 'seafoam-light.css'),
      array('file' => 'snerx.css'),
      array('file' => 'solarized-dark.css'),
      array('file' => 'solarized-light.css'),
      array('file' => 'sovl.css'),
      array('file' => 'tempus-cozette.css'),
      array('file' => 'tomorrow.css'),
      array('file' => 'tomorrow2.css'),
      array('file' => 'vapor.css'),
      array('file' => 'win95.css'),
      array('file' => 'yotsuba-b.css'),
      array('file' => 'yotsuba.css'),
    ),
  ),
);
return $fePkgs;

?>
