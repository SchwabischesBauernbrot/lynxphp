<?php

// FIXME: we need access to package
$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

// call backend handler to delete banner
$result = $pkg->useResource('del', array( 'boardUri' => $boardUri ));
if ($result === '1') {
  // success
  redirectTo(BASE_HREF . $boardUri . '/settings/banners');
} else {
  wrapContent('Something went wrong...');
}

?>
