<?php

$params = $get();

$boardUri = $params['params']['boardUri'];

// get board settings
$board = getBoard($boardUri, array('jsonFields' => array('settings')));
if (!$board) {
  return sendResponse2(array(), array(
    'code' => 404,
    'err' => 'Board does not exist',
  ));
}

$board['settings']['post_queueing'] = $_POST;
$ok = saveBoardSettings($boardUri, $board['settings']);

sendResponse2(array(
  'success'  => $ok ? 'true' : 'false',
  'boardUri' => $boardUri,
  'board' => $board,
  'post' => $_POST,
));

?>