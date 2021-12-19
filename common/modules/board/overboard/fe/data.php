<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/overboard.html',
        'handler' => 'overboard',
        /*
        'cacheSettings' => array(
          'files' => array(
            // theme is also would affect this caching
            'templates/header.tmpl', // wrapContent
            'templates/footer.tmpl', // wrapContent
          ),
        ),
        */
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
      // add Overboard to site naviagtion
      array(
        'pipeline' => 'PIPELINE_SITE_LEFTNAV',
        'module' => 'site_nav',
      ),
    ),
  ),
);
return $fePkgs;

?>
