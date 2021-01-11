<?php
$params = $get();

if (!hasPostVars(array('bannerId'))) {
  return;
}
$bannerId = (int)$_POST['bannerId'];
global $db, $models;
$res = $db->delete($models['board_banner'],array('criteria'=>array(
  array('bannerid', '=', $bannerId),
)));
sendResponse($res);

?>
