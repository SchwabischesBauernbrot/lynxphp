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
      'boardThread' => array(
        'route' => '/:board/thread/:thread',
        'file'  => 'board_thread',
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