<?php

$params = $get();

if (!hasPostVars(array('em'))) {
  // hasPostVars already outputs
  return;
}
// require being logged in
$user_id = loggedIn();
if (!$user_id) {
  return;
}

global $db, $models;
$row = array('email' => hash('sha512', BACKEND_KEY . $_POST['em'] . BACKEND_KEY));
$res = $db->updateById($models['user'], $user_id, $row);
sendResponse($res);

?>