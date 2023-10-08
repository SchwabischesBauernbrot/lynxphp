<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/admin/menus',
        'handler' => 'list',
        'portals' => array(
          'admin' => array()
        ),
      ),
    ),
    'forms' => array(
      /*
      array(
        'route' => '/:uri/settings/banners/add',
        'handler' => 'add',
      ),
      array(
        'route' => '/:uri/settings/banners/:id/delete',
        'handler' => 'delete',
      ),
      */
    ),
    'modules' => array(
      // add [pages] to admin nav
      array(
        'pipeline' => 'PIPELINE_ADMIN_NAV',
        'module'   => 'admin_nav',
      ),
    ),
  ),
);
return $fePkgs;

?>
