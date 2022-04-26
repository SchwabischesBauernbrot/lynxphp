<?php

$params = $getModule();

$navItem = array('label' => 'Overboard', 'destinations' => 'overboard.html');

if (1) {
  // FIXME: something better, maybe more like WP-menus
  $n = array();
  $added = false;
  foreach($io['navItems'] as $i) {
    $n[] = $i;
    // put after boards
    if (!$added && (!isset($i['label']) || $i['label'] === 'Boards')) {
      $n[] = $navItem;
      $added = true;
    }
  }
  $io['navItems'] = $n;
} else {
  $io['navItems'][] = $navItem;
}