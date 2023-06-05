<?php

$fePkgs = array(
  array(
    'handlers' => array(
      // might be able to combine these into the same handler, yea...
      array(
        'route'  => '/:uri/page/:page.html',
        'handler' => 'page',
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
          ),
          'posts' => array(
            'paramsCode' => array(
              'uri' => array('type' => 'params', 'name' => 'uri'),
              'page' => array('type' => 'params', 'name' => 'page'),
            ),
          ),
        ),
        //'func'   => 'getBoardThreadListingPageHandler',
        /*
        'options' => array(
          'cacheSettings' => array(
            'files' => array(
              // theme is also would affect this caching
              'templates/header.tmpl', // wrapContent
              'templates/footer.tmpl', // wrapContent
              'templates/mixins/board_header.tmpl', // board_portal
              'templates/mixins/board_footer.tmpl', // board_portal
              'templates/mixins/post_detail.tmpl', // renderPost
              'templates/mixins/post_actions.tmpl', // renderPostActions
            ),
          ),
        ),
        */
      ),
      array(
        'route'   => '/:uri/',
        'handler' => 'view',
        'portals' => array(
          'board' => array(
            'paramsCode' => array(
              'uri' => array('type' => 'params', 'name' => 'uri'),
            ),
          ),
          'posts' => array(
            'paramsCode' => array(
              'uri' => array('type' => 'params', 'name' => 'uri'),
              'page' => array('type' => 'params', 'name' => 'page'),
            ),
          ),
        ),
        //'func'   => 'getBoardThreadListingHandler',
        /*
        'options' => array(
          'cacheSettings' => array(
            'files' => array(
              // theme is also would affect this caching
              'templates/header.tmpl', // wrapContent
              'templates/footer.tmpl', // wrapContent
              'templates/mixins/board_header.tmpl', // board_portal
              'templates/mixins/board_footer.tmpl', // board_portal
              'templates/mixins/post_detail.tmpl', // renderPost
              'templates/mixins/post_actions.tmpl', // renderPostActions
            ),
          ),
        ),
        */
      ),
      /*
      array(
        'route'   => '/:uri/thread/:num.html',
        'handler' => 'view',
        //'func'   => 'getThreadHandler',
        'options' => array(
          'cacheSettings' => array(
            'files' => array(
              // theme is also would affect this caching
              'templates/header.tmpl', // wrapContent
              'templates/footer.tmpl', // wrapContent
              'templates/mixins/board_header.tmpl', // board_portal
              'templates/mixins/board_footer.tmpl', // board_portal
              'templates/mixins/post_detail.tmpl', // renderPost
              'templates/mixins/post_actions.tmpl', // renderPostActions
            ),
          ),
        ),
      ),
      */
    ),
    'css' => array(
      array('file' => 'board_banner.css')
    ),
    'js' => array(
      array('file' => 'refresh_boardpage.js')
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
