<?php

$params = $get();

$uri      = $params['params']['boardUri'];
$threadId = $params['params']['threadId'];
$postId   = $params['params']['postId'];
//$react    = $params['params']['react'];

$posts_model = getPostsModel($uri);
// FIXME: is this right?
if (!$posts_model) {
  return sendResponse(array(), 404, $uri . 'does not exist');
}

global $db;
$post = $db->findById($posts_model, $postId);
$data = json_decode($post['json'], true);
$id = getIdentity();
unset($data['reacts'][$id]);
$post['json'] = json_encode($data);

$res = $db->updateById($posts_model, $postId, $post);

sendResponse2(array(
  'uri' => $uri,
  'threadId' => $threadId,
  'postId' => $postId,
  'success' => $res,
));

?>
