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
    // can't use 401, it's not a log in issue
    'code' => 400,
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
$ip = getip();
$res = $db->find($models['post_queue_vote'], array(
  'criteria' => array(
    'queueid' => $queueid,
    'id' => $id,
    // doesn't matter what ip we had when we voted
    //'ip' => $ip,
  ),
), 'voteid');
$votes = $db->toArray($res);

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

$consensus = 2;

$sc = 0;
if (count($votes) >= $consensus) {
  //print_r($votes);
  foreach($votes as $i=>$v) {
    $sc += $v['bet'];
    unset($votes[$i]['ip']); // don't leak ip
  }
  $okToNuke = true;
  // approved to post
  //echo "score[$sc] > [", ($consensus / 2), "]<br>\n";
  if ($sc > ($consensus / 2)) {
    $okToNuke = post_dequeue($queueid);
  }
  if ($okToNuke) {
    // delete from queue
    $db->deleteById($models['post_queue'], $queueid);
    // delete all votes from db
    $db->delete($models['post_queue_vote'], array(
      'criteria' => array('queueid' => $queueid)
    ));
  }
}

sendResponse2(array(
  'success'  => $ok ? 'true' : 'false',
  'vote'     => $action,
  'queueid'  => $queueid,
  'created'  => $id,
  'boardUri' => $boardUri,
  'score'    => $sc,
  'votes'    => $votes,
));

?>