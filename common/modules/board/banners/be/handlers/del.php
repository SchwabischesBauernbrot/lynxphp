<?php
$params = $get();

$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return sendResponse(array(), 401, 'Need to be BO');
}

if (!hasPostVars(array('bannerId'))) {
  return sendResponse(array(), 400, 'Banner ID required');
}
$bannerId = (int)$_POST['bannerId'];
global $db, $models;
$res = $db->delete($models['board_banner'],array('criteria'=>array(
  array('bannerid', '=', $bannerId),
)));
sendResponse($res);

?>
