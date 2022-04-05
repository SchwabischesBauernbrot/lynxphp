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

// generate identity

$id = getIdentity();
//$ip = getip();
global $db, $models;

//echo "id[$id]<br>\n";

// get a list of our votes
$res = $db->find($models['post_queue_vote'], array(
  'criteria' => array(
    //'ip' => $ip,
    'id' => $id,
  ),
), 'queueid');
$votes = $db->toArray($res);
$nqis = array();
foreach($votes as $v) {
  $nqis[] = $v['queueid'];
}
// then cancel out queueid
//print_r($votes);

$crit = array('board_uri' => $boardData['uri']);
if (count($nqis)) {
  // remove any we've already voted on
  $crit[] = array('queueid', 'not in', $nqis);
}
// find a random post
$res = $db->find($models['post_queue'], array(
  'criteria' => $crit,
  'order' => 'random()',
  'limit' => 1,
));
$qps = $db->toArray($res);

if (!count($qps)) {
  sendResponse2(null, array(
    'meta' => array('board' => $boardData, 'votes' => $votes)
  ));
  return;
}
$qp = $qps[0];

// type, name, size, hash
$in_files = json_decode($qp['files'], true);
//print_r($in_files);
unset($qp['files']);
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

$post = json_decode($qp['post'], true);
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