<?php

$bePkgs = array(
  array(
    'models' => array(
      array(
        'name'   => 'board_log',
        'fields' => array(
          'userid'  => array('type' => 'int'),
          'boardid' => array('type' => 'int'),
          'global'  => array('type' => 'bool'),
          'type'    => array('type' => 'str'),
          'reason'  => array('type' => 'str'),
          'target'  => array('type' => 'str'),
          'targetids' => array('type' => 'str'),
          'relatedids' => array('type' => 'str'),
        ),
      ),
    ),
    'modules' => array(
      // is this needed?
      // well we could inject this data into some other endpoints...
      //array('pipeline' => 'PIPELINE_BOARD_DATA', 'module' => 'boardData'),
    ),
  ),
);
return $bePkgs;

?>
