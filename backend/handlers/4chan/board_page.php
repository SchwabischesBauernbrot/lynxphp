<?php

// Indexes
// https://a.4cdn.org/po/2.json

$boardUri = $request['params']['board'];
$page = str_replace('.json', '', $request['params']['page']);
$posts_model = getPostsModel($boardUri);
if (!$posts_model) {
  echo '[]';
  return;
}
$threads = boardPage($boardUri, $posts_model, $page);
$res = array(
  'threads' => $threads,
);
if (getQueryField('prettyPrint')) {
  echo '<pre>', json_encode($res, JSON_PRETTY_PRINT), "</pre>\n";
} else {
  echo json_encode($res);
}