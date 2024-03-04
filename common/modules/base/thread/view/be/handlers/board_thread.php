<?php

// board data + thread data
// would be good to include the banners data too
// need a pipeline for that..

global $tpp;
$boardUri = $request['params']['uri'];
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
//echo "<pre>", print_r($boardData, 1), "</pre>\n";
if (!$boardData) {
  return sendResponse2(array(), array(
    'code' => 404,
    'err'  => 'Board does not exist',
  ));
}
$posts_model = getPostsModel($boardUri);
$threadNum = (int)str_replace('.json', '', $request['params']['num']);
$boardData['threadCount'] = getBoardThreadCount($boardUri, $posts_model);
$boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);
$boardData['posts'] = getThread($boardUri, $threadNum, array('posts_model' => $posts_model));

$io = array(
  'out' => $boardData,
  // board Uri is in out.board.uri too
  // boardData doesn't have this...
  //'boardid' => $row['boardid'],
  'uri' => $boardUri,
  'tno' => $threadNum,
  // can we put models into io?!?
);

global $pipelines;
$pipelines[PIPELINE_THREAD_PAGE_DATA]->execute($io);

sendResponse2($io['out']);