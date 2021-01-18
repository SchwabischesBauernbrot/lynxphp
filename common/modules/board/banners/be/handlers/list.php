<?php
$params = $get();

$boardData = boardMiddleware($request);
if (!$boardData) {
  return sendResponse(array());
}

global $db, $models;
$res = $db->find($models['board_banner'], array('criteria' => array(
  array('board_id', '=', $boardData['boardid']),
)));
$banners = $db->toArray($res);
sendResponse($banners);

?>
