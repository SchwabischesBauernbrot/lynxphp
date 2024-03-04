<?php
return array(
  'name' => 'users_mgmt',
  'version' => 1,
  'portals' => array(
    'admin' => array(
      //'fePipelines' => array('PIPELINE_ADMIN_HEADER_TMPL', 'PIPELINE_ADMIN_FOOTER_TMPL'),
      //'requires' => array(''),
      // cacheSettings for backend module
    ),
  ),
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
      'name' => 'list_search',
      'params' => array(
        'endpoint' => 'opt/admin/users',
        'method' => 'POST',
        'unwrapData' => true,
        'requireSession'=> true,
        'requires' => array('publickey', 'email'),
        'params' => 'postdata',
      ),
    ),
    array(
      'name' => 'listgroups',
      'params' => array(
        'endpoint' => 'opt/admin/groups',
        'unwrapData' => true,
        'requireSession'=> true,
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
    array(
      'name' => 'deleteuser',
      'params' => array(
        'endpoint' => 'opt/admin/users/:id',
        'method' => 'DELETE',
        'unwrapData' => true,
        'requireSession'=> true,
        //'requires' => array('groups'),
        //'params' => 'postdata',
      ),
    ),
    array(
      'name' => 'boards_list',
      'params' => array(
        'endpoint' => 'opt/admin/boards',
        'unwrapData' => true,
        'requireSession'=> true,
      ),
    ),
    array(
      'name' => 'boards_delete',
      'params' => array(
        'endpoint' => 'opt/admin/boards/:id',
        'method' => 'DELETE',
        'unwrapData' => true,
        'requireSession'=> true,
        //'requires' => array('groups'),
        //'params' => 'postdata',
      ),
    ),
    array(
      'name' => 'get_config',
      'params' => array(
        'endpoint' => 'opt/admin/config',
        'unwrapData' => true,
        'requireSession'=> true,
        //'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
    ),

  ),
);
?>