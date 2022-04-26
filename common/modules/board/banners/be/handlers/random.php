<?php
$params = $get();

$boardUri = getQueryField('boardUri');
$boardData = boardMiddleware($request);
if (!$boardData && $boardUri !== 'overboard') {
  return; // middleware sends response
}

global $db, $models;
$crit = array();

if ($boardUri !== 'overboard') {
  $crit[] = array('board_id', '=', $boardData['boardid']);
}

$res = $db->find($models['board_banner'], array('criteria' => $crit));
$banners = $db->toArray($res);
if (!count($banners)) {
  return sendResponse(array());
}
shuffle($banners);
sendResponse($banners[0]);
?>
