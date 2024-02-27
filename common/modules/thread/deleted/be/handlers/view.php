<?php

// board data + thread data
// would be good to include the banners data too
// need a pipeline for that..

global $tpp;
$boardUri = $request['params']['uri'];
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
if (!$boardData) {
  return sendResponse2(array(), array(
    'code' => 404,
    'err'  => 'Board does not exist',
  ));
}
$posts_model = getPostsModel($boardUri);
$threadNum = (int)str_replace('.json', '', $request['params']['num']);
$boardData['threadCount'] = getBoardThreadCount($boardUri, $posts_model);
$boardData['pageCount'] = ceil($boardData['threadCount'] / $tpp);
$boardData['posts'] = getThread($boardUri, $threadNum, array('includeDeleted' => true, 'posts_model' => $posts_model));
sendResponse2($boardData);