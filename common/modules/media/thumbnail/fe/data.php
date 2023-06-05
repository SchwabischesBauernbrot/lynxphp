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
      /*
      // add post queuing to boards settings
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_NAV',
        'module' => 'setting_nav',
      ),
      */
    ),
  ),
);

?>