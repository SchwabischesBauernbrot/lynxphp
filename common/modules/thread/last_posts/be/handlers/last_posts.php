<?php

// board data + thread data
// would be good to include the banners data too
// need a pipeline for that..

global $tpp;
$boardUri = $request['params']['boardUri'];
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
if (!$boardData) {
  return sendResponse2(array(), array(
    'code' => 404,
    'err'  => 'Board does not exist',
  ));
}
$posts_model = getPostsModel($boardUri);
$threadNum = (int)str_replace('.json', '', $request['params']['thread']);
$boardData['threadCount'] = getBoardThreadCount($boardUri, $posts_model);
$boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);
$boardData['posts'] = getThread($boardUri, $threadNum, array('posts_model' => $posts_model));
$boardSettings[$boardUri] = $boardData['settings'];

// probably don't need to pass the meta but it's good form...
unset($boardData['settings']); // well lets not duplicate
sendResponse2($boardData, array(
  'meta'=> array(
    'boardSettings' => $boardSettings,
  ),
));