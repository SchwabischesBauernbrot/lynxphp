<?php

return array(
  array(
    'handlers' => array(
    ),
    'forms' => array(
      array(
        'route'   => '/admin/settings/:section',
        'handler' => 'admin_settings',
      ),
      array(
        'route'   => '/admin/settings/:section/add',
        'handler' => 'admin_settings_add',
      ),
      array(
        'route'   => '/admin/settings/:section/remove',
        'handler' => 'admin_settings_remove',
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
