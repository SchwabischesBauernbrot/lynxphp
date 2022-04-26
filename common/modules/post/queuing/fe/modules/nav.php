<?php

$params = $getModule();

$show = false;

// see if any of $io['boardSettings']['post_queueing'] are set to com
if (isset($io['boardSettings']['post_queueing'])) {
  foreach($io['boardSettings']['post_queueing'] as $t => $action) {
    if ($action === 'com' || $action === 'mod') {
      if ($action === 'com') {
        $show = true;
      } else {
        // mod
        // or if you're logged in as a mod...
        $show = true;
      }
      break;
    }
  }
}

// only show there's content in the queue
//print_r($io['boardSettings']['post_queueing']);

// only show it if we need to
if ($show) {
  // we'd have to make a backend call to check on every board page
  // maybe using JS to solve this is better?
  global $portalData;
  $count_html = '';
  if (isset($portalData['board']['post_queueing']['count'])) {
    if ($portalData['board']['post_queueing']['count']) {
      $count_html = ' (' . $portalData['board']['post_queueing']['count'] . ')';
    } else {
      // skip including it because there's nothing to moderate (at a community level?)
      return;
    }
  }
  // just always show it for now
  $boardUri = $io['boardUri'];
  $io['navItems']['Moderate' . $count_html] = $boardUri . '/moderate.html';
}

?>
