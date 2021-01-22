<?php
$params = $get();

$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return;
}

$settings = getBoardSettings($boardUri);
$settings['json'] = json_decode($settings['json'], true);

// include owned boards, groups...
sendResponse($settings);

?>
