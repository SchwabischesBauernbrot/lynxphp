<?php

$params = $get();

$uri      = $params['params']['boardUri'];
$threadId = $params['params']['threadId'];
$postId   = $params['params']['postId'];
$react    = $params['params']['react'];

// are reacts enabled?
$boardData = getBoard($uri, array('jsonFields' => 'settings'));
if (!$boardData) {
  return sendResponse2(array(), array(
    'code' => 404,
    'err'  => 'Board does not exist',
  ));
}

$posts_model = getPostsModel($uri);
// FIXME: is this right?
if (!$posts_model) {
  return sendResponse2(array(), array(
    'code' => 404,
    'err'  => 'Board does not exist: ' . $uri,
  ));
}

if (empty($boardData['settings']['react_mode'])) {
  return sendResponse2(array(), array(
    'code' => 400,
    'err'  => 'Board does not have reacts enabled: ' . $uri,
  ));
}

if (!empty($shared[$boardData['settings']['react_mode']])) {
  $filter = $shared[$boardData['settings']['react_mode']];
  if ($filter) {
    //print_r($filter);
    $ok = false;
    foreach($filter as $f) {
      if ($f === $react) {
        $ok = true;
        break;
      }
    }
    if (!$ok) {
      return sendResponse2(array(), array(
        'code' => 400,
        'err'  => 'Board does not allow this reacts: ' . $react,
      ));
    }
  }
}

global $db;
$post = $db->findById($posts_model, $postId);
$data = json_decode($post['json'], true);
$id = getIdentity();
$data['reacts'][$id] = $react;
$post['json'] = json_encode($data);

$res = $db->updateById($posts_model, $postId, $post);

sendResponse2(array(
  'uri' => $uri,
  'threadId' => $threadId,
  'postId' => $postId,
  'success' => $res,
  //'post' => $post,
));

?>
