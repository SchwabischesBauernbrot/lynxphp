<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/admin/strings',
        'handler' => 'admin_strings',
        'portals' => array('admin' => array()),
      ),
      /*
      array(
        'method'  => 'GET',
        'route'   => '/:uri/banners.html',
        'handler' => 'public_list',
        'cacheSettings' => array(
          'files' => array(
            // theme is also would affect this caching
            'templates/header.tmpl', // wrapContent
            '../common/modules/board/banners/fe/views/banner_listing.tmpl', // homepage
            'templates/footer.tmpl', // wrapContent
          ),
        ),
      ),
      */
    ),
    'forms' => array(
      // FIXME: ...
      array(
        'route' => '/admin/strings/add',
        'handler' => 'add',
      ),
      /*
      array(
        'route' => '/:uri/settings/banners/:id/delete',
        'handler' => 'delete',
      ),
      */
    ),
    'dependencies' => array('post/queuing'),
    'modules' => array(
      // add queue to admin navigation
      array(
        'pipeline' => 'PIPELINE_ADMIN_NAV',
        'module' => 'nav_admin',
      ),
      array(
        'pipeline' => 'PIPELINE_FE_ADMIN_QUEUE_ROW',
        'module' => 'admin_queue',
      ),
    ),
  ),
);
return $fePkgs;

?>
