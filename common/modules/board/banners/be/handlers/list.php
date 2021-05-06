<?php
$params = $get();

$boardData = boardMiddleware($request);
if (!$boardData) {
  return sendResponse(array());
}

global $db, $models, $tpp;
$res = $db->find($models['board_banner'], array('criteria' => array(
  array('board_id', '=', $boardData['boardid']),
)));
$banners = $db->toArray($res);
// just pass through the settings for now...
boardRowFilter($boardData, $boardData['json'], array('jsonFields' => 'settings'));
// I don't think this is required
$boardData['threadCount'] = getBoardThreadCount($boardData['uri']);
$boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);

sendResponse($banners, 200, '', array('board' => $boardData));

?>
