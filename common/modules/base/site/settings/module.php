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