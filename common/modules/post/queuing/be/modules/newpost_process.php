<?php

$module = $getModule();

$boardUri = $io['boardUri'];
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

// FIXME: how do we handle reloads? i.e. multiple posts of the same thing

if (!isset($boardData['json']['settings']['queueing_mode'])) {
  $boardData['json']['settings']['queueing_mode'] = '';
}

if ($boardData['settings']['queueing_mode'] === 'community') {
  $io['addToPostsDB'] = false;
  global $db, $models;
  $id = $db->insert($models['post_queue'], array(array(
    'board_uri' => $boardUri,
    'post'  => json_encode($io['p']),
    'files' => $io['files'],
    'type'  => 'com',
  )));
  $io['returnId'] = array(
    'status' => 'queued',
    'as' => $id,
  );
} else
if ($boardData['settings']['queueing_mode'] === 'moderator') {
  $io['addToPostsDB'] = false;
  global $db, $models;
  $id = $db->insert($models['post_queue'], array(array(
    'board_uri' => $boardUri,
    'post'  => json_encode($io['p']),
    'files' => $io['files'],
    'type' => 'mod',
  )));
  $io['returnId'] = array(
    'status' => 'queued',
    'as' => $id,
  );
}

?>