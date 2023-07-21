<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/admin/work_queue',
        'handler' => 'admin_queue',
        'portals' => array(
          'admin' => array()
        ),
      ),
    ),
    'forms' => array(
    ),
    'modules' => array(
      // add queues to admin navigation
      array(
        'pipeline' => 'PIPELINE_ADMIN_NAV',
        'module' => 'nav_admin',
      ),
    ),
  ),
);
return $fePkgs;

?>
