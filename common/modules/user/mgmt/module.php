<?php
return array(
  'name' => 'user_mgmt',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'list',
      'params' => array(
        'endpoint' => 'opt/admin/users',
        'unwrapData' => true,
        'sendSession'=> true,
        //'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
    ),
  ),
);
?>