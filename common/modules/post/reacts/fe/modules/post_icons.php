<?php

// FIXME: we need access to package
$params = $getModule();

if (!empty($io['p']['exposedFields']['reacts'])) {
  //print_r($io['p']['exposedFields']['reacts']);
  $io['icons'][] = array(
    'icon' => 'Redundant',
    'title' => 'Redundant 1',
  );
}

?>
