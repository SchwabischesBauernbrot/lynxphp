<?php

$params = $getModule();

if (DEV_MODE) {
  $io['actions']['admin'][] = array('target' => 'media', 'link' => '/backend/opt/boards/' . $io['boardUri'] . '/posts/' . $io['path']['postNum'] . '/media_debug?prettyPrint=1', 'label' => 'media_debug');
}

?>