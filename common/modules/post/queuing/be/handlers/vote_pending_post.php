<?php

$params = $get();

//$boardUri = $request['params']['uri'];
$boardUri = $_GET['boardUri'];
//echo "boardUri[$boardUri]<br>\n";
$row = getBoardRaw($boardUri);
if (!$row) {
  sendResponse2(array(), array(
    'code' => 404,
    'err' => 'Board does not exist',
  ));
}

$action = $_POST['vote'];

$sesRow = getSession();
if (!$sesRow) {
  // we just need to make a new session
  // but that needs to happen in the get
  return sendResponse2(array('success' => 'false'), array(
    'code' => 401,
    'err'  => 'bad session',
    // could put this in data too I suppose
    'meta' => array('vote' => $action),
  ));
}
$sesRow['json'] = json_decode($sesRow['json'], true);
$queueid = $sesRow['json']['queueid'];
if (!$queueid) {
  return sendResponse2(array('success' => 'false'), array(
    'code' => 500,
    'err'  => 'bad queueid',
    // could put this in data too I suppose
    'meta' => array('vote' => $action),
  ));
}
global $db, $models;

// do we already have a vote for this recorded?
$id = getIdentity();
$res = $db->find($models['post_queue_vote'], array(
  'criteria' => array(
    'queueid' => $queueid,
    'id' => $id,
    // doesn't matter what ip we had when we voted
    //'ip' => $ip,
  ),
), 'voteid');
$votes = $db->toArray($res);

$ip = getip();
$ok = false;
if (count($votes)) {
  // delete all of them
  foreach($votes as $v) {
    $db->deleteById($models['post_queue_vote'], $v['voteid']);
  }
}

// insert
if (1) {
  $ok = $db->insert($models['post_queue_vote'], array(array(
    'queueid' => $queueid,
    'id' => $id,
    'ip' => $ip,
    'bet' => $action === 'allow' ? 1 : 0,
  )));
} else {
  $ok = true;
}

// make post live?
$res = $db->find($models['post_queue_vote'], array(
  'criteria' => array(
    'queueid' => $queueid,
  ),
));
$votes = $db->toArray($res);

// if anything breaks here, there's not much we can do
// might be better to do it in a worker
// we could report error to admin

if (count($votes) > 2) {

  /*
  // get post/files data
  $qp = $db->findById($models['post_queue'], $queueid);
  // queueid, post, json, created_at, updated_at, type, board_uri,
  // files, thread_id
  //print_r($qp);
  // insert into board posts
  $boardUri = $qp['board_uri']; // just incase we voted across boards
  $post = json_decode($qp['post'], true);

  //echo "boardUri[$boardUri] post[$post]<br>\n";
  $posts_model = getPostsModel($boardUri);
  $id = 0;
  if ($posts_model && $post) {
    $id = $db->insert($posts_model, array($post));

    // handle files
    processFiles($boardUri, $qp['files'], $qp['thread_id'] ? $qp['thread_id'] : $id, $id);

    // bump board
    global $now;
    $inow = (int)$now;
    $urow = array('last_thread' => $inow, 'last_post' => $inow);
    $db->update($models['board'], $urow, array('criteria' => array(
      'uri' => $boardUri,
    )));

    if ($qp['thread_id']) {
      // bump thread
      $urow = array();
      $db->update($posts_model, $urow, array('criteria'=>array(
        'postid' => $qp['thread_id'],
      )));
    }

    if (1) {
      // delete from queue
      $db->deleteById($models['post_queue'], $queueid);
      // delete all votes from db
      $db->delete($models['post_queue_vote'], array(
        'criteria' => array('post_queueid' => $queueid)
      ));
    }
  }
  */
}

sendResponse2(array(
  'success' => $ok ? 'true' : 'false',
  'vote' => $action,
  'queueid' => $queueid,
  'created' => $id,
  'boardUri' => $boardUri,
));

?>