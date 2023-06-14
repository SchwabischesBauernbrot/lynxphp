<?php
// we need to be able to pass options
// probably separate the layout options from the backend otpions
$obPortals = array(
  'board' => array(
    // special overboard boardData
    'pageCount' => 1,
    'uri' => 'overboard',
    'title' => 'All Boards',
    'description' => 'posts across the site',
    'settings' => array(),
    'noBackendData' => true,
    //'pagenum' => $pagenum,
    'noBoardHeaderTmpl' => true, // controls banner
    'isThread' => true, // turn off paging
    //'isCatalog' => true, // prefix title
    'threadClosed' => true, // turn off post form
    'paramsCode' => array('page' => array('type' => 'params', 'name' => 'page'))
  ),
  'posts' => array(
    'uri' => 'overboard',
    'settings' => array(),
    'noBackendData' => true,
    //'noBoardHeaderTmpl' => true, // controls banner
    //'isCatalog' => true, // prefix title
    'threadClosed' => true, // turn off post form
    'paramsCode' => array(),
  ),
);

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/overboard/page/:page.html',
        'handler' => 'overboard',
        'portals' => $obPortals,
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
        'route'   => '/overboard.html',
        'handler' => 'overboard',
        'portals' => $obPortals,
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
      array(
        'pipeline' => 'PIPELINE_BOARD_NAV',
        'module' => 'board_nav',
      ),
    ),
  ),
);
return $fePkgs;

?>
