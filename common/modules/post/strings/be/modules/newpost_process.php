<?php

$module = $getModule();

if (!$io['addToPostsDB']) {
  // we're already not adding this post to the db for some reason
  // so no need to queue it
  return;
}

$boardUri = $io['boardUri'];
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

global $db, $models;
$res = $db->find($models['post_string']);
$strings = $db->toArray($res);

$ok = true;
$action = 0;
foreach($strings as $s) {
  if (strpos($s['string'], $io['p']['com']) !== false) {
    // contains $s['string']
    $ok = false;
    // maybe we go over all of them?
    $action = $s['action'];
    break;
  }
}

if (!$ok) {
  $io['addToPostsDB'] = false;
  $io['returnId'] = array(
    'status' => 'refused',
  );
}

?>