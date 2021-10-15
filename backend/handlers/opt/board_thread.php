<?php

// board data + thread data
// would be good to include the banners data too
// need a pipeline for that..

global $tpp;
$boardUri = $request['params']['board'];
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
if (!$boardData) {
  echo '[]';
  return;
}
$posts_model = getPostsModel($boardUri);
$threadNum = (int)str_replace('.json', '', $request['params']['thread']);
$boardData['threadCount'] = getBoardThreadCount($boardUri, $posts_model);
$boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);
$boardData['posts'] = getThread($boardUri, $threadNum, $posts_model);
sendResponse($boardData);