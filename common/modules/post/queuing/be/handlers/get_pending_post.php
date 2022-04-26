<?php
$params = $get();

// we need a page count
$boardData = boardMiddleware($request, array('getPageCount' => true));
if (!$boardData) {
  return;
}

// confirm we're in community mode
if (isset($boardData['json']['settings']['queueing_mode']) &&
    $boardData['json']['settings']['queueing_mode'] !== 'community') {
  return sendResponse(array(), 200, array('board' => $boardData));
}

$ip = getip();
if ($ip === '::1' || $ip === '127.0.0.1') {
  return sendResponse2(false, array(
    'code' => 400, 'meta' => array('ip' => $ip, 'server' => $_SERVER), 'err' => 'invalid ip',
  ));
  return sendResponse(array(), 400, array('ip' => $ip));
}

$votes = getYourVotes();
$qp = getYourNextQueue($boardData['uri'], $votes);

if (!$qp) {
  sendResponse2(null, array(
    'meta' => array('board' => $boardData, 'votes' => $votes)
  ));
  return;
}

$data = json_decode($qp['data'], true);

// type, name, size, hash
$in_files = $data['files'];

//unset($qp['files']);
$post_files = array();
foreach($in_files as $f) {
  $m6 = substr($f['type'], 0, 6);
  $isImage = $m6 === 'image/';
  $sizes = array(0, 0);
  // FIXME: make thumbnail in tmp
  if ($isImage) {
    $sizes = getimagesize('storage/tmp/' . $f['hash']);
  }
  // FIXME: copied...
  list($tn_w, $tn_h) = scaleSize($sizes[0], $sizes[1], 240);
  $post_files[] = array(
    'path' => 'storage/tmp/' . $f['hash'],
    'filename' => $f['name'],
    'size' => $f['size'],
    'w' => $sizes[0],
    'h' => $sizes[1],
    'tn_w' => (int)$tn_w,
    'tn_h' => (int)$tn_h,
  );
}

$post = $data['post'];
postDBtoAPI($post);
$qp['post'] = $post;
// simulate post date
$qp['post']['created_at'] = $qp['created_at'];
$qp['post']['files'] = $post_files;

// ensure browse will have a session
$setCookie = NULL;
$userid = getUserID(); // are we logged in?
$sesRow = ensureSession($userid); // sends a respsone on 500
if (!$sesRow) {
  return; // 500
}
//print_r($sesRow);
// did we just make it?
global $now;
if (isset($sesRow['created']) && (int)$sesRow['created'] === (int)$now) {
  // not going to have a username to send
  $setCookie = array(
    'name'  => 'session',
    'value' => $sesRow['session'],
    'ttl'   => $sesRow['expires'],
  );
}
// put it into our session
// FIXME: multiple tabs...
$sesRow['json'] = json_decode($sesRow['json'], true);
$sesRow['json']['queueid'] = $qp['queueid'];
//print_r($sesRow['json']);
global $db, $models;
$ok = $db->updateById($models['session'], $sesRow['sessionid'], array('json' => $sesRow['json']));
//echo "ok[$ok]<br>\n";

/*
// just pass through the settings for now...
boardRowFilter($boardData, $boardData['json'], array('jsonFields' => 'settings'));
// I don't think this is required
$posts_model = getPostsModel($boardData['uri']);
$boardData['threadCount'] = getBoardThreadCount($boardData['uri'], $posts_model);
$boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);
*/

//sendResponse($qp, 200, '', array('board' => $boardData, 'votes' => $votes));
sendResponse2($qp, array(
  'meta' => array('board' => $boardData, 'votes' => $votes, 'sessionStatus' => $ok, 'setCookie' => $setCookie,)
));

?>