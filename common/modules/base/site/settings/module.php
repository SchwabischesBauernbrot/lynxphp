<?php
return array(
  'name' => 'base_settings',
  'version' => 1,
  'resources' => array(
    // admin settings
    array(
      'name' => 'settings',
      'params' => array(
        'endpoint' => 'opt/settings',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array(),
        'params' => 'querystring',
        'cacheSettings' => array(
          // site_settings
          // user/setting
          // user_sessions but we only care if the data changes tbh
          'databaseTables' => array('users', 'site_settings'),
          //'files' => array(),
        ),
      ),
    ),
    array(
      'name' => 'save_settings',
      'params' => array(
        'endpoint' => 'opt/settings',
        'method' => 'POST',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('logo'),
        'params' => 'postdata',
      ),
    ),
  ),
);
?>