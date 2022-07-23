<?php

$module = $getModule();

if (!empty($io['fields']['reacts'])) {
  $counts = array();
  $your = getIdentity();
  foreach($io['fields']['reacts'] as $id => $v) {
    if (!isset($counts[$v])) $counts[$v] = 1;
    else
      $counts[$v]++;
    if ($id === $your) {
      $io['fields']['your_react'] = $v;
    }
  }
  $io['fields']['reacts'] = $counts;
}
//print_r($module);

?>
