<?php
$params = $get();

$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return;
}

$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

sendResponse($boardData);

?>
