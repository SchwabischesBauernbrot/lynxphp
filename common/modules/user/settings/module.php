<?php
return array(
  'name' => 'user_setting',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'settings',
      'params' => array(
        'endpoint' => 'opt/user/settings',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array(),
        'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'save_settings',
      'params' => array(
        'endpoint' => 'opt/users/settings',
        'method' => 'POST',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array(),
        'params' => 'postdata',
      ),
    ),
  ),
);
?>