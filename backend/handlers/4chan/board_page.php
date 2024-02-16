<?php

// Indexes
// https://a.4cdn.org/po/2.json

$boardUri = $request['params']['board'];
$page = str_replace('.json', '', $request['params']['page']);
$posts_model = getPostsModel($boardUri);
if (!$posts_model) {
  return sendJson(array('meta' => array('err' => 'Board not found')), array('code' => 404));
}
$threads = boardPage($boardUri, $posts_model, $page);
$res = array(
  'threads' => $threads,
);
sendJson($res);