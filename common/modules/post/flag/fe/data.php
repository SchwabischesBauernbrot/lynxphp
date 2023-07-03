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
    'modules' => array(
      // add flags to post form
      array(
        'pipeline' => 'PIPELINE_POST_FORM_FIELDS',
        'module' => 'post_field',
      ),
    ),
  ),
);
return $fePkgs;

?>
