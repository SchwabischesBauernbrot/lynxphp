<?php

$fePkgs = array(
  array(
    'handlers' => array(
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
    //'dependencies' => array('post/queuing'),
    'modules' => array(
      array(
        'pipeline' => 'PIPELINE_FE_ADMIN_QUEUE_ROW',
        'module' => 'admin_queue',
      ),
    ),
  ),
);
return $fePkgs;

?>
