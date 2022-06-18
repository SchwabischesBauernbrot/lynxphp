<?php

$params = $getModule();

// $io is fields
$io['settings_react_mode'] = array(
  'label' => 'Reacts mode',
  'type'  => 'select',
  'options' => array(
    'no' => 'No reacts',
    'signal3' => '3 basic reacts',
    'signal7' => '7 basic reacts',
    'all' => 'All reacts',
    'custom' => 'Custom reacts',
  ),
);

?>