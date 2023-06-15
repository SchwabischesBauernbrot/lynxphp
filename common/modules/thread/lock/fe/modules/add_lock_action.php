<?php

$params = $getModule();

if (empty($io['p']['closed'])) {
  $io['actions']['bo'][] = array(
    'link'  => $io['boardUri'] . '/thread/' . $io['p']['no'] . '/lock',
    'label' => 'Lock Thread',
    'includeWhere' => true,
  );
} else {
  $io['actions']['bo'][] = array(
    'link'  => $io['boardUri'] . '/thread/' . $io['p']['no'] . '/unlock',
    'label' => 'Unlock Thread',
    'includeWhere' => true,
  );
}

?>
