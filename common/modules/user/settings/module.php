<?php
return array(
  'name' => 'user_setting',
  'version' => 1,
  'portals' => array(
    'userSettings' => array(
      //'fePipelines' => array('PIPELINE_BOARD_SETTING_HEADER_TMPL'),
      //'requires' => array('boardUri'),
    ),
  ),  'resources' => array(
    array(
      'name' => 'settings',
      'params' => array(
        'endpoint' => 'opt/user/settings',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array(),
        'params' => 'querystring',
      ),
      'cacheSettings' => array(
        'databaseTables' => array('user')
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