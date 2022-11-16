<?php

// boardUri, boardName, boardDescription, session
$user_id = loggedIn();
if (!$user_id) {
  return;
}
if (!hasPostVars(array('boardUri', 'boardName', 'boardDescription'))) {
  // hasPostVars already outputs
  return; // sendResponse(array(), 400, 'Requires boardUri, boardName and boardDescription');
}
$boardUri = $_POST['boardUri'];

$res = createBoard($boardUri, $_POST['boardName'], $_POST['boardDescription'], $user_id);
if (is_array($res) && isset($res['errors'])) {
  return sendResponse2(array(), array(
    'code' => empty($res['code']) ? 500 : $res['code'],
    'err'  => join("<br>\n", $res['errors']),
  ));
}

$data = 'unknown';
if (is_numeric($res)) {
  $data = 'ok';
}
sendResponse($data);