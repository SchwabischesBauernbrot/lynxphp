<?php

// FIXME: we need access to package
$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;
global $beRrsc_del;
$call = $beRrsc_del;
$call['formData'] = array('bannerId'=>$request['params']['id']);
$result = consume_beRsrc($call);
if ($result === '1') {
  // success
  redirectTo(BASE_HREF . $boardUri . '/settings/banners');
} else {
  wrapContent('Something went wrong...');
}

?>
