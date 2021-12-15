<?php

$params = $getModule();

if (!empty($io['p']['cyclic'])) {
  $io['icons'][] = array(
    'icon'  => 'cyclic',
    'title' => 'Thread is cyclical',
  );
}

?>
