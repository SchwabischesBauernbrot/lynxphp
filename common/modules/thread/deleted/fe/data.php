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
      // from base/thread/view
      array(
        'route'   => '/:uri/threads/deleted/:num.html',
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
        // why options instead of diect cacheSettings?
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
