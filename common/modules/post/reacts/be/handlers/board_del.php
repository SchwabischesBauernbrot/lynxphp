<?php
$params = $get();

/*
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return sendResponse(array(), 401, 'Need to be BO');
}
*/
$boardUri = $params['params']['boardUri'];
if (!$boardUri) {
  return sendResponse(array(), 400, 'Board URI required');
}
$reactid = $params['params']['reactid'];
if (!$reactid) {
  return sendResponse(array(), 400, 'React ID required');
}
global $db, $models;
// FIXME: check the DB to see if any one else is using this banner file
// if not delete it from disk!

$res = $db->delete($models['board_react'],array('criteria'=>array(
  array('reactid', '=', $reactid),
  array('board_uri', '=', $boardUri),
)));
sendResponse($res);

?>
