<?php

return array(
  array(
    'models' => array(
      // menu id can be strings...
      // menu (str), type, data, position
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
      /*
      // is this needed?
      // well we could inject this data into some other endpoints...
      array('pipeline' => PIPELINE_BOARD_DATA, 'module' => 'boardData'),
      */
    ),
  ),
);


?>
