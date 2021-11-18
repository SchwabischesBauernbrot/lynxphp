<?php

$fePkgs = array(
  array(
    'dependencies' => array(
      'user_mgmt',
    ),
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/user/settings/themedemo/:theme.html',
        'handler' => 'demo',
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
    ),
  ),
);
return $fePkgs;

?>
