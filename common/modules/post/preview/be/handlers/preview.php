<?php

// backend

$params = $get();

$boardUri = $params['params']['board'];
/*
$boardData = getBoardByUri($boardUri);
if (!$boardData) {
  sendResponse(array(), 404, 'Board does not exist');
  return;
}
*/

$id = ''.(int)str_replace('.json', '', $params['params']['id']);
if (!$id) {
  sendResponse(array(), 400, 'Invalid ID');
  return;
}

/*
$posts_model = getPostsModel($boardUri);
if ($posts_model === false) {
  // this board does not exist
  sendResponse(array(), 404, 'Board not found');
  return;
}
$post_files_model = getPostFilesModel($boardUri);
*/

$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

$post = getPost($boardUri, $id, false);
/*
$posts = array_filter($posts, function($p) use ($id) {
  //echo "p[", print_r($p, 1) ,"] id[$id]<Br>\n";
  return $p['no'] === $id;
});
$post = count($posts) ? $posts[0] : array();
*/

$res = array(
  'success' => 'ok', 'final' => $post, 'boardUri' => $boardUri, 'id' => $id,
  'postCount' => 0, // FIXME:
  'boardSettings' => empty($boardData['settings']) ? array() : $boardData['settings'],
);
sendJson($res);

?>