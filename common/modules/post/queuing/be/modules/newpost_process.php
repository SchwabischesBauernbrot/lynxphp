<?php

$module = $getModule();

$boardUri = $io['boardUri'];
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

// FIXME: how do we handle reloads? i.e. multiple posts of the same thing

if (!isset($boardData['settings']['queueing_mode'])) {
  $boardData['settings']['queueing_mode'] = '';
}

$tags = $io['p']['tags'];

// FIXME: move into newpost_tag pipeline
foreach($boardData['settings']['post_queueing'] as $t => $mode) {
  if ($mode && in_array($t, $tags)) {
    if ($mode === 'com') {
      // add this tag
      $io['p']['tags']['queue_com'] = true;
    } else
    if ($mode === 'mod') {
      // add this tag
      $io['p']['tags']['queue_mod'] = true;
    } else {
      // remove all these tags
      $io['p']['tags']['queue_com'] = false;
      $io['p']['tags']['queue_mod'] = false;
    }
  }
}

function queueIt($boardUri, $io, $type) {
  $threadid = $io['p']['threadid'];
  $ip = $io['priv']['ip'];
  $data = array(
    'post'  => $io['p'],
    'files' => json_decode($io['files'], true), // don't need to double wrap this
    'priv'  => $io['priv'],
  );
  $id = post_queue($boardUri, $ip, $threadid, $data, $type);
  return $id;
}

// FIXME: maybe explain what tags triggered the queueing?
if ($io['p']['tags']['queue_com']) {
  $io['addToPostsDB'] = false;
  // queue is defaulting com...
  $id = queueIt($boardUri, $io, 'com');
  $io['returnId'] = array(
    'status' => 'queued',
    'as' => $id,
  );
} else
if ($io['p']['tags']['queue_mod']) {
  $io['addToPostsDB'] = false;
  // queue is defaulting com...
  $id = queueIt($boardUri, $io, 'mod');
  $io['returnId'] = array(
    'status' => 'queued',
    'as' => $id,
  );
}

?>