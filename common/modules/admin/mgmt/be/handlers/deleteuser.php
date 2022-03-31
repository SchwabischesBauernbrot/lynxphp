<?php
$params = $get();

$res = userInGroupMiddleware($request, 'admin');
if (!$res) {
  // if no session, it will already handle output...
  return;
}

global $db, $models;

$id = $request['params']['id'];
if (!$id) {
  return sendResponse(array(
    'success' => false,
    'error' => 'Invalid userid'
  ));
}

sendResponse(array(
  'success' => deleteUser($id),
));

?>
