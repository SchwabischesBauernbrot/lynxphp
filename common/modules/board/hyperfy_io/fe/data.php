<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/:uri/hyperfy.html',
        'handler' => 'hyperfy',
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
        'portals' => array(
          'board' => array(
            'paramsCode' => array(
              // allows remapping
                // uri => params but then not extensible
                // what else would we need?
                // processing options can come after the extraction?
              'uri' => array('type' => 'params', 'name' => 'uri'),
              'page' => array('type' => 'params', 'name' => 'page'),
            ),
            'threadClosed' => true,
          ),
          /*
          'posts' => array(
            'paramsCode' => array(
              'uri' => array('type' => 'params', 'name' => 'uri'),
              'page' => array('type' => 'params', 'name' => 'page'),
            ),
          ),
          */
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
      // add [Multiplayer] to board naviagtion
      array(
        'pipeline' => 'PIPELINE_BOARD_NAV',
        'module' => 'nav',
      ),
    ),
  ),
);
return $fePkgs;

?>
