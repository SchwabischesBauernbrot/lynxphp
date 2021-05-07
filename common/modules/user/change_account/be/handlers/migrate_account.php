<?php

$params = $get();

if (!hasPostVars(array('pk'))) {
  // hasPostVars already outputs
  return;
}

// require being logged in
$user_id = loggedIn();
if (!$user_id) {
  return;
}

global $db, $models;

// pass it in as hex, so you can't easily correlate it with challenge request
$row = array('publickey' => $_POST['pk']);
$res = $db->updateById($models['user'], $user_id, $row);
sendResponse($res);

?>