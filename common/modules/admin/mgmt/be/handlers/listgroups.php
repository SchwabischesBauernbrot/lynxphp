<?php
$params = $get();

$res = userInGroupMiddleware($request, 'admin');
if (!$res) {
  // if no session, it will already handle output...
  return;
}

global $db, $models;

$res = $db->find($models['group'], array('order' => 'groupid'));
$arr = $db->toArray($res);
//print_r($arr);
$users = pluck($arr, array(
  'groupid', 'name'
));
// include owned boards, groups...
sendResponse($users);

?>
