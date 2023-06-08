<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/:uri/thread/:num.html',
        'handler' => 'view',
        //'func'   => 'getThreadHandler',
        'portals' => array(
          'board' => array(
            'paramsCode' => array(
              // allows remapping
                // uri => params but then not extensible
                // what else would we need?
                // processing options can come after the extraction?
              'uri' => array('type' => 'params', 'name' => 'uri'),
              'num' => array('type' => 'params', 'name' => 'num'),
            ),
            'isThread' => true,
          ),
          'posts' => array(
            'paramsCode' => array(
              'uri' => array('type' => 'params', 'name' => 'uri'),
              'num' => array('type' => 'params', 'name' => 'num'),
            ),
            'isThread' => true,
          ),
        ),
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
      array(
        'route'   => '/:uri/thread/:num_inline.html',
        'handler' => 'inline_view',
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
      array(
        'route'   => '/:boardUri/thread/:num/refresh',
        'handler' => 'refresh',
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
    ),
    'js' => array(
      array('file' => 'refresh_thread.js')
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
