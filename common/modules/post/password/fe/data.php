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
      array(
        'route'   => '/:uri/posts/:pno/delete',
        'handler' => 'delete',
      ),
    ),
    'modules' => array(
      // add [Delete] to post actions
      array('pipeline' => 'PIPELINE_POST_ACTIONS', 'module' => 'post_actions',),
      // FIXME: add to post form
    ),
  ),
);
return $fePkgs;

?>
