<?php

//
// Optimized routes for lynxphp
//

return array(
  'opt' => array(
    'dir' => 'opt',
    'routes' => array(
      'check' => array(
        // db check
        'route' => '/check',
        'file'  => 'check',
      ),
      'session' => array(
        'route' => '/session',
        'file'  => 'session',
        'cacheSettings' => array(
          'databaseTables' => array('user_sessions'),
        ),
      ),
      'boardPage' => array(
        'route' => '/boards/:uri/:page',
        'file'  => 'board_page',
        'cacheSettings' => array(
            'databaseTables' => array('user_sessions', 'board_{{uri}}_public_posts',
              'board_{{uri}}_public_post_files', 'boards'),
        ),
      ),
      'boardThread' => array(
        'route' => '/:board/thread/:thread',
        'file'  => 'board_thread',
      ),
      // https://a.4cdn.org/po/catalog.json
      'boardCatalog' => array(
        'route' => '/:board/catalog.json',
        'file'  => 'board_catalog',
      ),
      'boardsJson' => array(
        'route' => '/boards.json',
        'file'  => 'boards_json',
      ),
      'myBoards' => array(
        'route' => '/myBoards',
        'file'  => 'my_boards',
      ),
      'perms' => array(
        'route' => '/perms/:perm',
        'file'  => 'route_opt_perms',
      ),
      // has to be last...
      // non-standard 4chan api - lets disable for now
      // /opt should have replaced this
      'boardJson' => array(
        'route' => '/:board', // .json
        'file'  => 'board_json',
      ),
    ),
  ),
);