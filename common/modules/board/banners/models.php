<?php

global $db, $models;

$board_banners_model = array(
  'name'   => 'board_banner',
  'fields' => array(
    'board_id' => array('type' => 'int'),
    'image'    => array('type' => 'str'),
    'w'        => array('type' => 'int'),
    'h'        => array('type' => 'int'),
    'weight'   => array('type' => 'int'),
  )
);

$db->autoupdate($board_banners_model);

$models['board_banner'] = $board_banners_model;

?>
