<?php

// Thread endpoint
// https://a.4cdn.org/po/thread/570368.json

$boardUri = $request['params']['board'];
$threadNum = (int)str_replace('.json', '', $request['params']['thread']);
$posts_model = getPostsModel($boardUri);
if (!$posts_model) {
  echo '[]';
  return;
}
$posts = getThread($boardUri, $threadNum, false);
// board doesn't not exist
if (getQueryField('prettyPrint')) {
  echo '<pre>', json_encode($posts, JSON_PRETTY_PRINT), "</pre>\n";
} else {
  echo json_encode($posts);
}
