<?php

// pipelines:
// - boardData

//$bePkg = new backend_package($this);
$bePkg = $this->makeBackend();


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

$module = new pipeline_module('board_banners');
$module->dependencies = array();
$module->preempt      = array();
// is this needed?
// well we could inject this data into some other endpoints...
$module->attach('boardData', function(&$row) {
});

?>
