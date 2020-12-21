<?php
$params = $get();

$boardData = boardMiddleware($request);
global $db, $models;
$res = $db->find($models['board_banner'], array('criteria' => array(
  array('board_id', '=', $boardData['boardid']),
)));
$banners = $db->toArray($res);
if (!count($banners)) {
  return sendResponse(array());
}
shuffle($banners);
sendResponse($banners[0]);
?>
