<?php

$params = $get();

//$boardUri = $params['params']['uri'];

// verify if BO
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return;
}

$posts_model = getPostsModel($boardUri);

// could list all delete posts might be easiest

// partial/completely delete threads?
global $db;
// , 'deleted' => 1

// list all threads and note which ops are deleted and which have deleted posts... <=
$res = $db->find($posts_model, array('criteria' => array('threadid' => 0), 'order' => 'postid desc'), 'postid,deleted,sub');
$posts = $db->toArray($res);
$db->free($res);
// could we get reply count? undeleted/deleted....
foreach($posts as $i => $p) {
  $replies = $db->count($posts_model, array('criteria' => array('threadid' => $p['postid'])));
  $deleted_replies = $db->count($posts_model, array('criteria' => array('threadid' => $p['postid'], 'deleted' => 1)));
  $posts[$i]['replies'] = $replies;
  $posts[$i]['del_replies'] = $deleted_replies;
  // normalize from various db types
  $posts[$i]['deleted'] = $db->isTrue($p['deleted']);
}

$res = $db->find($posts_model, array('criteria' => array('deleted' => 1), 'order' => 'postid desc'), 'postid,deleted,sub');
$dposts = $db->toArray($res);
$db->free($res);

sendResponse2(array(
  'threads' => $posts,
  'deleted' => $dposts,
));