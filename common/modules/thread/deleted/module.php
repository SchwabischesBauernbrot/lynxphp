<?php
return array(
  'name' => 'thread_delete',
  'version' => 1,
  'settings' => array(
    array(
      'level' => 'bo', // constant?
      'location' => 'thread', // /tab/group
      'locationLabel' => 'Thread settings',
      'addFields' => array(
        'vaccum_threads' => array(
          'label' => 'nuke deleted threads after 30 days',
          'type'  => 'checkbox',
        ),
      )
    ),
  ),
  'resources' => array(
    array(
      'name' => 'list',
      'params' => array(
        'endpoint' => 'doubleplus/:uri/threads/deleted',
        'unwrapData' => true,
        'sendSession' => true,
      ),
    ),
    array(
      'name' => 'view',
      'params' => array(
        'endpoint' => 'doubleplus/:uri/threads/deleted/:num',
        'unwrapData' => true,
        'sendSession' => true,
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
    array(
      'name' => 'scrub_post',
      'params' => array(
        'method' => 'DELETE',
        'endpoint' => 'doubleplus/:uri/posts/:pno',
        'unwrapData' => true,
        'sendSession' => true,
      ),
    ),
    array(
      'name' => 'scrub_thread',
      'params' => array(
        'method' => 'DELETE',
        'endpoint' => 'doubleplus/:uri/threads/:tno',
        'unwrapData' => true,
        'sendSession' => true,
      ),
    ),
    array(
      'name' => 'undel_post',
      'params' => array(
        'method' => 'POST',
        'endpoint' => 'doubleplus/:uri/posts/:pno/undelete',
        'unwrapData' => true,
        'sendSession' => true,
      ),
    ),
    array(
      'name' => 'undel_thread',
      'params' => array(
        'method' => 'POST',
        'endpoint' => 'doubleplus/:uri/threads/:tno/undelete',
        'unwrapData' => true,
        'sendSession' => true,
      ),
    ),
  ),
);
?>
