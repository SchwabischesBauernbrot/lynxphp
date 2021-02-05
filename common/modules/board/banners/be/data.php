<?php

$bePkgs = array(
  array(
    'models' => array(
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
    ),
    'modules' => array(
      // is this needed?
      // well we could inject this data into some other endpoints...
      array('pipeline' => PIPELINE_BOARD_DATA, 'module' => 'boardData'),
    ),
  ),
);
return $bePkgs;

?>
