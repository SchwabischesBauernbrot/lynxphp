<?php

// FIXME: we need access to package
$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

// call backend handler to delete banner
$result = $pkg->useResource('del', array(
  'bannerId' => $request['params']['id'],
  'boardUri'=> $boardUri, // FIXME: shouldn't have to pass this but we do, fix backend middleware options
));
if ($result === true) {
  // success
  redirectTo(BASE_HREF . $boardUri . '/settings/banners');
} else {
  wrapContent('Something went wrong... Error: ' . print_r($result, 1));
}

?>
