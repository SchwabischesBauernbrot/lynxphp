<?php

$params = $get();

// verify if BO
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return;
}
$tno = $params['params']['tno'];

// how?
// just update with a single query eh? unsoftdel
global $db, $models;
// probably shouldn't touch the overboard...

$posts_model = getPostsModel($boardUri);
$db->update($posts_model, array('deleted' => false), array('criteria' => array(
  array('postid', '=', $tno),
  array('deleted', '=', true),
)));
$db->update($posts_model, array('deleted' => false), array('criteria' => array(
  array('threadid', '=', $tno),      
  array('deleted', '=', true),
)));

sendResponse2(array(
  'status' => 'ok',
  'debug' => array(
    'tno' => $tno,
  )
));