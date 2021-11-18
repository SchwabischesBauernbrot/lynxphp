<?php

// non-standard 4chan api - lets disable for now

global $db, $models, $tpp;
$boardUri = str_replace('.json', '', $request['params']['board']);
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
if (!$boardData) {
  return sendResponse2(array(), array(
    'code' => 404,
    'err'  => 'Board does not exist',
  ));
}
$posts_model = getPostsModel($boardUri);
$boardData['threadCount'] = getBoardThreadCount($boardUri, $posts_model);
$boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);
sendResponse2($boardData);