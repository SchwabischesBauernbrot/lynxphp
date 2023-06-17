<?php

return array(
  array(
    'handlers' => array(
    ),
    'forms' => array(
      array(
        'route'   => '/admin/settings/:section',
        'handler' => 'admin_settings',
        'portals' => array(
          'admin' => array()
        ),
      ),
      array(
        'route'   => '/admin/settings/:section/add',
        'handler' => 'admin_settings_add',
        'portals' => array(
          'admin' => array()
        ),
      ),
      array(
        'route'   => '/admin/settings/:section/remove',
        'handler' => 'admin_settings_remove',
        'portals' => array(
          'admin' => array()
        ),
      ),
    ),
    'modules' => array(
      /*
      // add [Banner] to board naviagtion
      array(
        'pipeline' => 'PIPELINE_BOARD_NAV',
        'module' => 'nav',
      ),
      */
    ),
  ),
);

?>
