<?php

$params = $getModule();

if (empty($io['p']['cyclic'])) {
  $io['actions']['bo'][] = array(
    'link'  => $io['boardUri'] . '/thread/' . $io['p']['no'] . '/cyclical',
    'label' => 'Cyclical Thread',
  );
} else {
  $io['actions']['bo'][] = array(
    'link'  => $io['boardUri'] . '/thread/' . $io['p']['no'] . '/uncyclic',
    'label' => 'Decyclical Thread',
  );
}
?>
