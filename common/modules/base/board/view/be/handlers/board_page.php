<?php

// base/board/view - be module

global $tpp;

$boardUri = $request['params']['uri'];
$pageNum = $request['params']['page'] ? (int)$request['params']['page'] : 1;

list($row, $boardData) = getBoardWithBoardid($boardUri, array('jsonFields' => 'settings'));
if (!$row) {
  return sendResponse2(array(), array(
    'code' => 404,
    'err'  => 'Board does not exist',
  ));
}
// board hasn't set anything up yet
// but we need to make sure we do
if (!isset($boardData['settings'])) {
  $boardData['settings'] = array();
}
// json fields to pull in the settings, to avoid future calls
$posts_model = getPostsModel($boardUri);

$threadCount = getBoardThreadCount($boardUri, $posts_model);
$threads = boardPage($boardUri, $posts_model, $pageNum);

$io = array(
  'out' => array(
    'board' => $boardData,
    'page1' => $threads,
    'threadsPerPage'   => $tpp,
    'threadCount' => $threadCount,
    'pageCount' => ceil($threadCount/$tpp),
  ),
  // board Uri is in out.board.uri too
  'boardid' => $row['boardid'],
);

global $pipelines;
$pipelines[PIPELINE_BOARD_PAGE_DATA]->execute($io);

sendResponse2($io['out']);