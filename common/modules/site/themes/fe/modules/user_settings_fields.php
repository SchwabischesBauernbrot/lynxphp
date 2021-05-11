<?php

$params = $getModule();

if ($io['category'] === 'theme') {
  $io['fields']['current_theme'] = array(
    'label' => 'Theme',
    'type'  => 'themethumbnails',
    'options' => $shared['themes'],
  );
  $io['fields']['code_theme'] = array(
    'label' => 'Code Theme',
    'type'  => 'select',
    'options' => array(),
  );
}

?>