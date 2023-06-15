<?php

$params = $getModule();

if (empty($io['p']['sticky'])) {
  $io['actions']['bo'][] = array(
    'link'  => $io['boardUri'] . '/thread/' . $io['p']['no'] . '/pin',
    'label' => 'Pin Thread',
    'includeWhere' => true,
  );
} else {
  $io['actions']['bo'][] = array(
    'link'  => $io['boardUri'] . '/thread/' . $io['p']['no'] . '/unpin',
    'label' => 'Unpin Thread',
    'includeWhere' => true,
  );
}

?>
