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
      // track threads ourself
      array('pipeline' => PIPELINE_POST_ADD, 'module' => 'post_add'),
    ),
  ),
);


?>
