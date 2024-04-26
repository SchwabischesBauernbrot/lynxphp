<?php

$params = $get();

// verify if BO
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return;
}
$pno = $params['params']['pno'];

// how?
// just update with a single query eh? unsoftdel
global $db, $models;
// probably shouldn't touch the overboard...

$posts_model = getPostsModel($boardUri);
$db->update($posts_model, array('deleted' => false), array('criteria' => array(
  array('postid', '=', $pno),      
  array('deleted', '=', true),
)));

$res = $db->find($posts_model, array('criteria' => array('postid' => $pno)));
$post = $db->get_row($res);
$db->free($res);

sendResponse2(array(
  'status' => 'ok',
  'tno' => $post['threadid'] ? $post['threadid'] : $pno,
  'debug' => array(
    'pno' => $pno,
  )
));