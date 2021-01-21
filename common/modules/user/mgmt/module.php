<?php
return array(
  'name' => 'users_mgmt',
  'version' => 1,
  'resources' => array(
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
  ),
);
?>