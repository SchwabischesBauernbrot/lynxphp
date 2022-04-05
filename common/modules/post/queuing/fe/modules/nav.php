<?php

$params = $getModule();

//print_r($io['boardSettings']);

// only show it if we need to
if (isset($io['boardSettings']['queueing_mode']) &&
    $io['boardSettings']['queueing_mode'] === 'community') {
  $boardUri = $io['boardUri'];
  $io['navItems']['[Moderate]'] = $boardUri . '/moderate.html';
}

?>
