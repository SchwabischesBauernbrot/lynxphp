<?php

$params = $getModule();

/*
if (0) {
  $n = array();
  foreach($io['navItems'] as $l => $us) {
    if ($l === 'Boards') $n['Overboard'] = 'overboard.html';
    $n[$l] = $us;
  }
  $io['navItems'] = $n;
} else {
*/
$io['navItems']['Overboard'] = 'overboard.html';
//}