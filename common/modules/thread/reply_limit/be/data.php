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
      // handle incoming posts (though we only need to be on replies)
      // why not just reject the reply on PIPELINE_REPLY_ALLOWED
      array('pipeline' => PIPELINE_REPLY_ALLOWED, 'module' => 'reply_allowed'),
      //array('pipeline' => PIPELINE_NEWPOST_PROCESS, 'module' => 'newpost_process'),
    ),
  ),
);


?>
