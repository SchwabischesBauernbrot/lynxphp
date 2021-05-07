<?php

return array(
  'user_mgmt' => array(
    'handlers' => array(
    ),
    'forms' => array(
      array(
        'route' => '/user/settings',
        'handler' => 'user_settings',
      ),
      array(
        'route' => '/user/settings/:category',
        'handler' => 'user_settings',
      ),
      /*
      array(
        'route' => '/admin/users/add',
        'handler' => 'add',
      ),
      array(
        'route' => '/admin/users/:id/groups',
        'handler' => 'editgroups',
      ),
      array(
        'route' => '/admin/users/:id/delete',
        'handler' => 'delete',
      ),
      */
    ),
    'modules' => array(
      /*
      // add [users] to admin nav
      array(
        'pipeline' => 'PIPELINE_ADMIN_NAV',
        'module'   => 'nav',
      ),
      */
    ),
    'pipelines' => array(
      array(
        // change the actual fields for settings
        'name' => 'PIPELINE_MODULE_USER_SETTINGS_FIELDS',
      ),
    ),
  ),
);

?>