<?php

$params = $getModule();

if (!empty($io['p']['closed'])) {
  $io['icons'][] = array(
    'icon'  => 'lock',
    'title' => 'Thread is locked',
  );
}

?>
