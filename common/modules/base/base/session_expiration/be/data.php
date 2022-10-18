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
      array('pipeline' => PIPELINE_SESSION_EXPIRATION, 'module' => 'session_expiration'),
    ),
  ),
);


?>
