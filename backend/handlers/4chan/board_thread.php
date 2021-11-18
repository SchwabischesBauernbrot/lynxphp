<?php

// Thread endpoint
// https://a.4cdn.org/po/thread/570368.json

$boardUri = $request['params']['board'];
$threadNum = (int)str_replace('.json', '', $request['params']['thread']);
$posts_model = getPostsModel($boardUri);
// board doesn't not exist
if (!$posts_model) {
  return sendRawResponse(array(), 404, 'Board not found');
}
$posts = getThread($boardUri, $threadNum, array('posts_model' => $posts_model));
sendRawResponse($posts);
