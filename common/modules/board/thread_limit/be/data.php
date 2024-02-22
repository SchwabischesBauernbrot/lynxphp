<?php

return array(
  array(
    'models' => array(
      /*
      array(
        'name'   => 'board_banner',
        'fields' => array(
          'board_id' => array('type' => 'int'),
          'image'    => array('type' => 'str'),
          'w'        => array('type' => 'int'),
          'h'        => array('type' => 'int'),
          'weight'   => array('type' => 'int'),
        ),
      ),
      */
    ),
    'modules' => array(
      // on thread creation
      // figure out if any threads need to be queue to be deleted
      array('pipeline' => PIPELINE_NEWPOST_PROCESS, 'module' => 'newthread_edge'),
    ),
    // should be a workqueue
    // check the state of the thread list
    // check for any archive options
    // we need to know when the archive is complete
    // and they need to be able to pause/stop any deletion
    // so almost a fan out WITH a confirmation that each one finished
    // dealer/mapreduce
    // threadnum, processorid, status (sent/cleared)
    // what are the conditions you don't need a record
    // well when you don't need it to wait
    // if no results where status=sent then it's good to start deletion
    // PIPELINE_WQ_REQUEST_DELETE_THREAD
    // PIPELINE_DELETE_THREAD
  ),
);


?>
