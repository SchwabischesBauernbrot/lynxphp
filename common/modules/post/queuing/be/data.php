<?php

return array(
  array(
    'models' => array(
      // would be nice to be per board
      // but I don't think we need that initially
      array(
        'name'   => 'post_queue',
        'fields' => array(
          // everything can be put in here
          // all metadata
          // anything we don't need to query on
          'board_uri' => array('type' => 'str'), // which community scope
          'thread_id' => array('type' => 'int'), // 0 for new thread
          'post'      => array('type' => 'text'),
          'files'     => array('type' => 'text'),
          // moderator / community
          'type'    => array('type' => 'str'),
        ),
      ),
      array(
        'name'   => 'post_queue_vote',
        'fields' => array(
          'queueid' => array('type' => 'int'),
          'id' => array('type' => 'str'),
          'ip' => array('type' => 'str'),
          'bet' => array('type' => 'int'),
        ),
        // indexes?
      ),
    ),
    'modules' => array(
      /*
      // is this needed?
      // well we could inject this data into some other endpoints...
      array('pipeline' => PIPELINE_BOARD_DATA, 'module' => 'boardData'),
      */
      array('pipeline' => PIPELINE_NEWPOST_PROCESS, 'module' => 'newpost_process'),
    ),
  ),
);


?>