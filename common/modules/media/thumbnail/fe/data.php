<?php

return array(
  array(
    'handlers' => array(
      // does thumbnail exist
      array(
        'route'   => '/:uri/thread/:threadid/post/:postid/thumbnail.json',
        'handler' => 'thumbnail',
      ),
      array(
        'route'   => '/:uri/posts/:postid/thumbnail.json',
        'handler' => 'thumbnail',
      ),
    ),
    'forms' => array(
      // these aren't cacheable period.
      /*
      array(
        'route' => '/:uri/settings/queueing',
        'handler' => 'board_setting',
      ),
      */
    ),
    'modules' => array(
      // add debug to media actions
      array(
        'pipeline' => 'PIPELINE_MEDIA_ACTIONS',
        'module' => 'media_actions',
      ),
    ),
  ),
);

?>