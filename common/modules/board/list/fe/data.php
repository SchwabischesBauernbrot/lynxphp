<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'  => '/boards.html',
        //'func'   => 'getBoardsHandler',
        'handler' => 'content',
        'cacheSettings' => array(
          'files' => array(
            'templates/header.tmpl', // wrapContent
            '../common/modules/board/list/fe/views/board_listing.tmpl', // board
            'templates/footer.tmpl', // wrapContent
          ),
        ),
      ),
      array(
        'route' => '/boards.php',
        'method' => 'POST',
        //'func'   => 'getBoardsHandler',
        'handler' => 'content',
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
      array(
        'route'  => '/boards_inline.html',
        //'func'   => 'getInlineBoardsHandler',
        'handler' => 'inline_content',
        'cacheSettings' => array(
          'files' => array(
            '../common/modules/board/list/fe/views/board_listing.tmpl', // board
          ),
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
return $fePkgs;

?>
