<?php

$boardUri = $io['boardUri'];
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

if (!isset($boardData['settings']['queueing_mode'])) {
  $boardData['settings']['queueing_mode'] = '';
}

// we only tag if settings are on
// add/remove queue_* tags
if (isset($boardData['settings']['post_queueing'])) {
  $tags = $io['tags'];
  foreach($boardData['settings']['post_queueing'] as $t => $mode) {
    if ($mode && in_array($t, $tags)) {
      if ($mode === 'com') {
        // add this tag
        $io['tags']['queue_com'] = true;
      } else
      if ($mode === 'mod') {
        // add this tag
        $io['tags']['queue_mod'] = true;
      } else {
        // remove all these tags
        // actually if the settings are off
        // off is the default tag state
        // turning them off again here would stop other modules from flipping them on
        //$io['tags']['queue_com'] = false;
        //$io['tags']['queue_mod'] = false;
      }
    }
  }
} 
// otherwise no queueing...
