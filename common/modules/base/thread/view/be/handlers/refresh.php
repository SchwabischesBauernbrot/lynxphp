<?php

// backend
$params = $get();

$boardData = boardMiddleware($request);
if (!$boardData) {
  return sendResponse(array());
}

$boardUri = $boardData['uri'];
$threadNum = (int)getQueryField('thread');
if (!$threadNum) {
  sendResponse(array(), 400, 'Invalid thread number');
  return;
}
$last = (int)getQueryField('last');
if (!$last) {
  sendResponse(array(), 400, 'Invalid post number');
  return;
}

// if we use getThread, we'd need the thread id to scope it...
$replies = getThread($boardUri, $threadNum, array(
  'since_id' => $last,
  'includeOP' => false,
));

$maxtime = 0;
foreach($replies as $r) {
  $maxtime = max($maxtime, $r['updated_at']);
}
if (!$maxtime) {
  global $now;
  $maxtime = $now;
}

sendResponse2($replies, array('mtime' => $maxtime));

?>