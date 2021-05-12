<?php

$fePkgs = array(
  array(
    'dependencies' => array(
      'user_mgmt',
    ),
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/user/settings/themedemo/:theme/',
        'handler' => 'demo',
        'cacheSettings' => array(
          'files' => array(
            //'common/modules/site/themes/fe/shared.php'
            'css/themes/{{route.theme}}.css'
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
