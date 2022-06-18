<?php
$params = $get();

$uri = $params['params']['boardUri'];

/*
$boardData = boardMiddleware($request);
if (!$boardData) {
  return sendResponse(array());
}
*/

global $db, $models, $tpp;

$res = $db->find($models['board_react'], array('criteria' => array(
  array('board_uri', '=', $uri),
)));
$reacts = $db->toArray($res);

//sendResponse($banners, 200, '', array('board' => $boardData));
sendResponse2($reacts);

?>
