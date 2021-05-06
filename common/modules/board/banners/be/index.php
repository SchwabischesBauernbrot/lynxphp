<?php

// set up backend specific code (models, modules)

// pipelines:
// - boardData

// $this is the package
$bePkg = $this->makeBackend();

// add backend models
$bePkg->addModel(array(
  'name'   => 'board_banner',
  'fields' => array(
    'board_id' => array('type' => 'int'),
    'image'    => array('type' => 'str'),
    'w'        => array('type' => 'int'),
    'h'        => array('type' => 'int'),
    'weight'   => array('type' => 'int'),
  )
));

// is this needed?
// well we could inject this data into some other endpoints...
$bePkg->addModule(PIPELINE_BOARD_DATA, 'boardData');

/*
$module = new pipeline_module('board_banners');
$module->dependencies = array();
$module->preempt      = array();
$module->attach('boardData', function(&$row) {
});
*/

?>
