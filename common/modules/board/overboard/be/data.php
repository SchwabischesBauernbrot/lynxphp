<?php

return array(
  array(
    'models' => array(
      array(
        'name'   => 'overboard_thread',
        'fields' => array(
          'uri'      => array('type' => 'str'),
          'thread_id' => array('type' => 'int'),
          // timestamp are already included
        ),
      ),
    ),
    'modules' => array(
      // track threads from post adds
      array('pipeline' => PIPELINE_POST_ADD, 'module' => 'post_add'),
      // track thread deletions
      array('pipeline' => PIPELINE_THREAD_PRE_DELETE, 'module' => 'thread_del'),
    ),
  ),
);


?>
