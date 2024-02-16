<?php

// queuing/be

// shared code for the backend

function getYourVotes() {
  // generate identity
  $id = getIdentity();
  //$ip = getip();

  // get a list of our votes
  global $db, $models;
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
  return $nqis;
}

function getYourQueue($boardUri, $votes = false) {
  // then cancel out queueid
  //print_r($votes);
  global $db, $models;

  if (!$votes) {
    $votes = getYourVotes();
  }

  $ip = getip();
  $crit = array('board_uri' => $boardUri,
// disable for development
//    array('ip', '!=', $ip,),
  );
  if (count($votes)) {
    // remove any we've already voted on
    $crit[] = array('queueid', 'not in', $votes);
  }
  //print_r($crit);
  // find a random post
  $res = $db->find($models['post_queue'], array(
    'criteria' => $crit,
    'order' => $db->randOrder(),
    'limit' => 1,
  ));
  $qps = $db->toArray($res);
  return $qps;
}

function getYourNextQueue($boardUri, $votes = false) {
  $qps = getYourQueue($boardUri, $votes);
  if (!count($qps)) {
    return false;
  }
  return $qps[0];
}

// post -> queue
// why notthread_id here too?
// what's type here?
function post_queue($boardUri, $ip, $thread_id, $data, $type) {
  global $db, $models;
  $id = $db->insert($models['post_queue'], array(array(
    'board_uri' => $boardUri,
    'thread_id' => $thread_id,
    'ip' => $ip,
    'type' => $type,
    'data'  => json_encode($data),
  )));
  return $id;
}

function post_queue_delete($queueid) {
  global $db, $models;
  if (is_array($queueid)) {
    // multiple

    // delete from queue
    $db->deleteByIds($models['post_queue'], $queueid);
    // delete all votes from db
    $db->delete($models['post_queue_vote'], array(
      'criteria' => array(array('queueid', 'in', $queueid))
    ));

  } else {
    // single
    // delete from queue
    $db->deleteById($models['post_queue'], $queueid);
    // delete all votes from db
    $db->delete($models['post_queue_vote'], array(
      'criteria' => array('queueid' => $queueid)
    ));
  }
}

// queue -> post
function post_dequeue($queueid) {
  global $db, $models;
  // get post/files data
  $qp = $db->findById($models['post_queue'], $queueid);
  // queueid, post, json, created_at, updated_at, type, board_uri,
  // files, thread_id
  //echo "<pre>qp[", print_r($qp, 1), "]</pre>\n";

  // insert into board posts
  $boardUri = $qp['board_uri']; // just incase we voted across boards
  $data = json_decode($qp['data'], true);

  $posts_model = getPostsModel($boardUri);
  $id = 0;
  $okToNuke = true;
  if ($posts_model && $data) {
    // FIXME: pipelines?

    $id = createPost($boardUri, $data['post'], json_encode($data['files']), $data['priv']);
    // $id maybe an array with file issues...

    /*
    $id = $db->insert($posts_model, array($data['post']));

    $posts_priv_model = getPrivatePostsModel($boardUri);
    $privPost = $data['priv'];
    $privPost['postid'] = $id; // update postid
    $db->insert($posts_priv_model, array($privPost));

    // handle files
    processFiles($boardUri, $data['files'], $qp['thread_id'] ? $qp['thread_id'] : $id, $id);

    // bump board
    global $now;
    $inow = (int)$now;
    $urow = array('last_thread' => $inow, 'last_post' => $inow);
    //echo "bumping[$boardUri] [", print_r($urow, 1), "]<br>\n";
    $db->update($models['board'], $urow, array('criteria' => array(
      'uri' => $boardUri,
    )));

    // do we need to bump thread?
    if ($qp['thread_id']) {
      // bump thread
      $urow = array();
      //echo "bumping thread[", $qp['thread_id'] ,"] [", print_r($urow, 1), "]<br>\n";
      $db->update($posts_model, $urow, array('criteria'=>array(
        'postid' => $qp['thread_id'],
      )));
    }
    */
  } else {
    // an honest attempt hasn't been made yet...
    $okToNuke = false;
  }
  return $okToNuke;
}

return true;

?>