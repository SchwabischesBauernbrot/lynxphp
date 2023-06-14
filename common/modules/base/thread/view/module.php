<?php
return array(
  'name' => 'thread_view',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'refresh',
      'params' => array(
        'endpoint' => 'opt/threadRefresh',
        'unwrapData' => true,
        'requires' => array('boardUri', 'thread', 'last'),
        'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'board_thread',
      'params' => array(
        'endpoint' => 'opt/:uri/thread/:num',
        'unwrapData' => true,
        //'sendSession' => true,
        'requires' => array('uri', 'num'),
        'params' => array(
          'params' => array('uri', 'num'),
        ),
        'cacheSettings' => array(
          // PIPELINE_BOARD_QUERY_MODEL could modify boards
          // posts/files
          // well boards will get bumped when there's a bump...
          // should be good enough for now
          'databaseTables' => array('user_sessions', 'board_{{uri}}_public_posts',
            'board_{{uri}}_public_post_files', 'boards'),
        ),
      ),
    ),

  ),
);
?>
