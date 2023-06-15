<?php

$params = $getModule();

if (empty($io['p']['cyclic'])) {
  $io['actions']['bo'][] = array(
    'link'  => $io['boardUri'] . '/thread/' . $io['p']['no'] . '/cyclical',
    'label' => 'Cyclical Thread',
    'includeWhere' => true,
  );
} else {
  $io['actions']['bo'][] = array(
    'link'  => $io['boardUri'] . '/thread/' . $io['p']['no'] . '/uncyclic',
    'label' => 'Decyclical Thread',
    'includeWhere' => true,
  );
}
?>
