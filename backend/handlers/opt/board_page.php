<?php

global $tpp;

$boardUri = $request['params']['uri'];
$pageNum = $request['params']['page'] ? (int)$request['params']['page'] : 1;


$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
if (!$boardData) {
  return sendResponse2(array(), array(
    'code' => 404,
    'err'  => 'Board does not exist',
  ));
}
$posts_model = getPostsModel($boardUri);

$threadCount = getBoardThreadCount($boardUri, $posts_model);
$threads = boardPage($boardUri, $posts_model, $pageNum);
sendResponse2(array(
  'board' => $boardData,
  'page1' => $threads,
  'threadsPerPage'   => $tpp,
  'threadCount' => $threadCount,
  'pageCount' => ceil($threadCount/$tpp),
));