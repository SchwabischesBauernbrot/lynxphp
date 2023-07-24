<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/:uri/catalog.html',
        'handler' => 'catalog',
        'portals' => array('board' => array(
          'isCatalog' => true,
          'paramsCode' => array('uri' => array('type' => 'params', 'name' => 'uri')),
        )),
        'cacheSettings' => array(
          'files' => array(
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
      // add [Catalog] to board naviagtion
      array(
        'pipeline' => 'PIPELINE_BOARD_NAV',
        'module' => 'nav',
      ),
    ),
  ),
);
return $fePkgs;

?>
