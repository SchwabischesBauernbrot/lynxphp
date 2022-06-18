<?php

return array(
  array(
    'models' => array(
      array(
        'name'   => 'board_react',
        'fields' => array(
          'board_uri' => array('type' => 'str'),
          'name'      => array('type' => 'str'),
          'text'      => array('type' => 'str'),
          'image'     => array('type' => 'str'),
          'w'         => array('type' => 'int'),
          'h'         => array('type' => 'int'),
          // requirements to use?
          'lock_default' => array('type' => 'int'), // if locked, you have to unlock it
          'hide_default' => array('type' => 'int'), // if hidden, you have to earn it to see it
          // minimum number of posts on the board
          // percs level?
          // post count?
          // react count?
        ),
      ),
    ),
    'modules' => array(
      array('pipeline' => PIPELINE_BE_POST_EXPOSE_DATA_FIELD, 'module' => 'expose_reacts'),
      array('pipeline' => PIPELINE_BE_POST_FILTER_DATA_FIELD, 'module' => 'filter_reacts'),
    ),
  ),
);


?>
