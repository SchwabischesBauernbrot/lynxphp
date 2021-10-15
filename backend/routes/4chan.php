<?php

// from 4chan API docs to keep in mind
/*
Do not make more than one request per second.
Thread updating should be set to a minimum of 10 seconds, preferably higher.
Use If-Modified-Since when doing your requests.
Make API requests using the same protocol as the app. Only use SSL when a user is accessing your app over HTTPS.
*/

return array(
  'opt' => array(
    'dir'  => '4chan',
    'routes' => array(
      // https://a.4cdn.org/boards.json
      'boards' => array(
        'route' => '/boards.json',
        'file'  => 'boards'
      ),
      // https://a.4cdn.org/po/catalog.json
      'route_4chan_boardCatalog' => array(
        'route' => '/:board/catalog.json',
        'file'  => 'board_catalog'
      ),
      // FIXME: https://a.4cdn.org/archive.json
      // https://a.4cdn.org/po/threads.json
      'route_4chan_boardThreads' => array(
        'route' => '/:board/threads.json',
        'file'  => 'board_threads'
      ),
      // Indexes
      // https://a.4cdn.org/po/2.json
      'route_4chan_boardPage' => array(
        'route' => '/:board/:page',
        'file'  => 'board_page'
      ),
      // Thread endpoint
      // https://a.4cdn.org/po/thread/570368.json
      'route_4chan_boardThread' => array(
        'route' => '/:board/thread/:thread',
        'file'  => 'board_thread'
      ),
    ),
  ),
);