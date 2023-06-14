<?php

// autosage/be

$module = $getModule();

// which thread
$tid = $io['p']['threadid']; // will be 0 if new thread
if (!$tid) {
  return;
}

$boardUri = $io['boardUri'];
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

//print_r($boardData['settings']);

// get board's reply limit
$limit = empty($boardData['settings']['reply_limit']) ? 0 : $boardData['settings']['reply_limit'];

// if no limit, not need to get ocunt
if (!$limit) return;

// how many posts in thread?
$cnt = getThreadPostCount($boardUri, $tid);

if ($cnt > $limit) {
  $io['allowed'] = false;
  $io['issues'][] = 'thread\'s reply limit hit';
}

?>