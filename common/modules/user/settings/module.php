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
/*
    array(
      'name' => 'list',
      'params' => array(
        'endpoint' => 'opt/admin/users',
        'unwrapData' => true,
        'requireSession'=> true,
        //'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'listgroups',
      'params' => array(
        'endpoint' => 'opt/admin/groups',
        'unwrapData' => true,
        'requireSession'=> true,
        //'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'updateusergroups',
      'params' => array(
        'endpoint' => 'opt/admin/users/:id/groups',
        'method' => 'POST',
        'unwrapData' => true,
        'requireSession'=> true,
        'requires' => array('groups'),
        'params' => 'postdata',
      ),
    ),
*/
  ),
);
?>