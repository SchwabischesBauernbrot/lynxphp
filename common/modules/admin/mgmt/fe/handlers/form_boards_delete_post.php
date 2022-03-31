<?php

$params = $getHandler();

$id = (int)$params['request']['params']['id'];
if (!$id) {
  return wrapContent('Invalid board');
}

wrapContent('Please wait...');
$res = $pkg->useResource('boards_delete', array('id' => $id));

if ($res['success']) {
  // maybe a js alert?
  echo "Success<br>\n";
  redirectTo('/admin/boards');
} else {
  wrapContent('Something went wrong...' . print_r($res, 1));
}

?>
