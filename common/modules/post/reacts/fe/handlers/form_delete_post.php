<?php

// FIXME: we need access to package
$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

// call backend handler to delete banner
$result = $pkg->useResource('board_del', array(
  'reactid' => $request['params']['id'],
  'boardUri'=> $boardUri, // FIXME: shouldn't have to pass this but we do, fix backend middleware options
));
if ($result === true) {
  // success
  global $BASE_HREF;
  redirectTo($BASE_HREF . $boardUri . '/settings/reacts.html');
} else {
  wrapContent('Something went wrong... Error: ' . print_r($result, 1));
}

?>
