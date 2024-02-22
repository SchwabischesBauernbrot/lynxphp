<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/:uri/threads/deleted',
        'handler' => 'list',
        /*
        'cacheSettings' => array(
          'files' => array(
            // theme is also would affect this caching
            'templates/header.tmpl', // wrapContent
            '../common/modules/board/banners/fe/views/banner_listing.tmpl', // homepage
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
      // add "Deleted Threads" link to board actions
      array(
        'pipeline' => 'PIPELINE_BOARD_ACTIONS',
        'module' => 'board_action',
      ),
      // maybe this should be like a board nav thing...
      // insert scrub post action
      // insert scrub entire thread action
    ),
  ),
);
return $fePkgs;

?>
