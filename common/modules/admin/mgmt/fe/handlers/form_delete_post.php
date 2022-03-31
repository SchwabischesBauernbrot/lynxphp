<?php

$params = $getHandler();

$id = (int)$params['request']['params']['id'];
if (!$id) {
  return wrapContent('Invalid user');
}

wrapContent('Please wait...');
$res = $pkg->useResource('deleteuser', array('id' => $id));

if ($res['success']) {
  // maybe a js alert?
  echo "Success<br>\n";
  redirectTo('/admin/users');
} else {
  wrapContent('Something went wrong...' . print_r($res, 1));
}

?>
