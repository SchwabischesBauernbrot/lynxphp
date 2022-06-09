<?php

$params = $getModule();

//if ($io['category'] === 'theme') {
  $io['default_theme'] = array(
    'label' => 'Default Theme',
    'type'  => 'select',
    'options' => $shared['themes'],
  );
/*
  $io['fields']['code_theme'] = array(
    'label' => 'Code Theme',
    'type'  => 'select',
    'options' => array(),
  );
*/
//}

?>