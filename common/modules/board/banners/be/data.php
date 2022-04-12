<?php

$bePkgs = array(
  array(
    'models' => array(
      array(
        'name'   => 'board_banner',
        'fields' => array(
          'board_id' => array('type' => 'int'),
          'image'    => array('type' => 'str'),
          'w'        => array('type' => 'int'),
          'h'        => array('type' => 'int'),
          'weight'   => array('type' => 'int'),
        ),
      ),
    ),
    'modules' => array(
      // is this needed?
      // well we could inject this data into some other endpoints...
      // we don't need EVERYWHERE, just on page, catalog
      // anything with board_portal
      //array('pipeline' => PIPELINE_BOARD_DATA, 'module' => 'board_data'),
      // this isn't going to work, we need a board_portal hook
      // but that's a frontend thing and we're not always sure we need it...
      // like how is the logs module going to know it needs to hook this?
      // maybe it could provide it's own hook but then banners would need to know about it...
      //array('pipeline' => PIPELINE_BOARD_PAGE_DATA, 'module' => 'board_page_data'),
      //array('pipeline' => PIPELINE_BOARD_CATALOG_DATA, 'module' => 'board_catalog_data'),
      //array('pipeline' => PIPELINE_BOARD_QUERY_MODEL, 'module' => 'board_query'),
    ),
  ),
);
return $bePkgs;

?>
