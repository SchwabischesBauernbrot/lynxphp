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
if (is_array($replies) && count($replies)) {
  foreach($replies as $i => $r) {
    $maxtime = max($maxtime, $r['updated_at']);
    // save some bandwidth
    $replies[$i]['exposedFields'] = array_filter($r['exposedFields'], function($value) { return !is_null($value) && $value !== ''; });
    $r['exposedFields'] = $replies[$i]['exposedFields'];
    $replies[$i] = array_filter($r, function($value) {
      $isNotEmpty = (!is_null($value) && $value !== '');
      $isEmptyArray = is_array($value) && count($value) === 0;
      return $isNotEmpty && !$isEmptyArray;
    });
  }
}
if (!$maxtime) {
  global $now;
  $maxtime = $now;
}

sendResponse2($replies, array('mtime' => $maxtime, 'meta' => array(
  'boardSettings' => array($boardUri => $boardData['settings']),
)));

?>