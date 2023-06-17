<?php

return array(
  'user_mgmt' => array(
    'handlers' => array(
      array(
        'route'   => '/admin.php',
        'handler' => 'home',
        'portals' => array(
          'admin' => array()
        ),
      ),
      array(
        'route'   => '/admin/modules.php',
        'handler' => 'modules',
        'portals' => array(
          'admin' => array()
        ),
      ),
      array(
        'route'   => '/admin/install.php',
        'handler' => 'install',
        'portals' => array(
          'admin' => array()
        ),
      ),
      array(
        'route'   => '/admin/fe_routes.php',
        'handler' => 'fe_routes',
        'portals' => array(
          'admin' => array()
        ),
      ),
      array(
        'route'   => '/admin/be_routes.php',
        'handler' => 'be_routes',
        'portals' => array(
          'admin' => array()
        ),
      ),
      array(
        'route'   => '/admin/users',
        'handler' => 'list',
        'portals' => array(
          'admin' => array()
        ),
      ),
      // search
      array(
        'route'   => '/admin/users',
        'method'  => 'POST',
        'handler' => 'list',
        'portals' => array(
          'admin' => array()
        ),
      ),
      array(
        'route'   => '/admin/boards',
        'handler' => 'boards_list',
        'portals' => array(
          'admin' => array()
        ),
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
        'portals' => array(
          'admin' => array()
        ),
      ),
      array(
        'route' => '/admin/users/:id/delete',
        'handler' => 'delete',
        'portals' => array(
          'admin' => array()
        ),
      ),
      array(
        'route' => '/admin/boards/:id/delete',
        'handler' => 'boards_delete',
        'portals' => array(
          'admin' => array()
        ),
      ),
    ),
    'modules' => array(
      // add [users] and [boards] to admin nav
      array(
        'pipeline' => 'PIPELINE_ADMIN_NAV',
        'module'   => 'nav',
      ),
    ),
  ),
);

?>