<?php

return array(
  'user_mgmt' => array(
    'handlers' => array(
      array(
        'route'   => '/admin/users',
        'handler' => 'list',
      ),
      // search
      array(
        'route'   => '/admin/users',
        'method'  => 'POST',
        'handler' => 'list',
      ),
      array(
        'route'   => '/admin/boards',
        'handler' => 'boards_list',
      ),
    ),
    'forms' => array(
      // not built yet:
      /*
      array(
        'route' => '/admin/users/add',
        'handler' => 'add',
      ),
      */
      array(
        'route' => '/admin/users/:id/groups',
        'handler' => 'editgroups',
      ),
      array(
        'route' => '/admin/users/:id/delete',
        'handler' => 'delete',
      ),
      array(
        'route' => '/admin/boards/:id/delete',
        'handler' => 'boards_delete',
      ),
    ),
    'modules' => array(
      // add [users] to admin nav
      array(
        'pipeline' => 'PIPELINE_ADMIN_NAV',
        'module'   => 'nav',
      ),
    ),
  ),
);

?>