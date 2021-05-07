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
      ),
    ),
    'forms' => array(),
    'modules' => array(
      // register form control
      array(
        'pipeline' => 'PIPELINE_FORM_WIDGET_THEMETHUMBNAILS',
        'module' => 'widget_themethumbnails',
      ),
      // register settings
      array(
        'pipeline' => 'PIPELINE_MODULE_USER_SETTINGS_FIELDS',
        'module' => 'user_settings_fields',
      ),
    ),
  ),
);
return $fePkgs;

?>
