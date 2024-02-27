<?php

//ldr_require('../frontend_lib/handlers/boards.php'); // preprocessPost
ldr_require('../common/modules/base/thread/view/fe/common.php'); // renderThread

$boardUri = $request['params']['uri'];
$tno = (int)str_replace('.html', '', $request['params']['num']);

//$boardData = getBoardThread($boardUri, $threadNum);
// need to git mv handler
global $boardData; // make it cachable
$boardData = $pkg->useResource('view', array('uri' => $boardUri, 'num' => $tno));
if ($boardData === false) {
  http_response_code(404);
  wrapContent('Board ' . $boardUri . ' does not exist');
  return;
}

// MISSING_BOARD just means no board key in data...
// empty may pick up an valid empty array
if (!isset($boardData['title']) || !isset($boardData['posts']) || $boardData['posts'] === false) {
  http_response_code(404);
  wrapContent('This thread does not exist');
  return;
}
// lynxchan bridge error handling:
// uri and settings: array(), pageCount: 15 will be set
if (!isset($boardData['title'])) {
  http_response_code(404);
  wrapContent('Board ' . $boardUri . ' does not exist');
  return;
}
if (!isset($boardData['posts'])) {
  http_response_code(404);
  wrapContent('This thread does not exist');
  return;
}

// renderThread($boardData, $boardUri, $tno, $options = false) {
$res = renderThread($boardData, $tno);

wrapContent($res['html'], array('title' => $res['title']));