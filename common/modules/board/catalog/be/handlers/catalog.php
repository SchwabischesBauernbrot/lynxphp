<?php

// https://a.4cdn.org/po/catalog.json
global $tpp;
$boardUri = $request['params']['board'];
$page = boardCatalog($boardUri);
if (!is_array($page)) {
  // boardCatalog handles this
  return;
}

// json fields to pull in the settings, to avoid future calls
list($row, $boardData) = getBoardWithBoardid($boardUri, array('jsonFields' => 'settings'));
if (!$row) {
  return sendResponse2(array(), array(
    'code' => 404,
    'err'  => 'Board does not exist',
  ));
}

$pages = count($page);
// FIXME: just return a list of threads...
// also be able to page count?
$res = array();
for($i = 1; $i <= $pages; $i++) {
  $res[] = array(
    'page' => $i,
    'threads' => $page[$i],
  );
}

$io = array(
  'out' => array(
    'pages' => $res,
    'board' => $boardData,
  ),
  'mtime' => $now,
  'meta' => array(),
  // board Uri is in out.board.uri too
  'boardid' => $row['boardid'],
);

global $pipelines;
$pipelines[PIPELINE_BOARD_CATALOG_DATA]->execute($io);

sendResponse2($io['out']);