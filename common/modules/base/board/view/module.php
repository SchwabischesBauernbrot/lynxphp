<?php
return array(
  'name' => 'base_board_view',
  'version' => 1,
  'portals' => array(
    'board' => array(
      'fePipelines' => array('PIPELINE_BOARD_HEADER_TMPL', 'PIPELINE_BOARD_FOOTER_TMPL'),
      'requires' => array('boardUri'),
      // cacheSettings for backend module
    ),
    'posts' => array(
      'requires' => array('boardUri'),
      // cacheSettings for backend module
    ),
  ),
  'resources' => array(
    array(
      'name' => 'board_page',
      'params' => array(
        'endpoint' => 'opt/boards/:uri/:page',
        'unwrapData' => true,
        'sendSession' => true,
        'requires' => array('uri', 'page'),
        'params' => array(
          'querystring' => 'portals',
          'params' => array('uri', 'page'),
        ),
        'cacheSettings' => array(
          'databaseTables' => array('user_sessions', 'board_{{uri}}_public_posts',
            'board_{{uri}}_public_post_files', 'boards'),
        ),
      ),
    ),
  ),
);
?>
