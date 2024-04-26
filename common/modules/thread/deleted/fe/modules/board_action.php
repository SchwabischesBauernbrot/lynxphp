<?php

$params = $getModule();

//print_r($io);

$boardUri = $io['boardUri'];

$io['actions']['bo'][] = array(
  'link' => '/' . $boardUri . '/threads/deleted', 'label' => 'Deleted Threads',
  //'includeWhere' => true,
);

?>
