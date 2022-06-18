<?php
$params = $get();

//print_r($params);

$boardUri = $params['params']['boardUri'];
//$textReact = $params['params']['react'];

/*
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return;
}
*/

$boardData = getBoardByUri($boardUri);
if (!$boardData) {
  return sendResponse2(array(), array(
    'code' => 404,
    'err'  => 'Board does not exist',
  ));
}
//$sizes = getimagesize($file);

//print_r($_POST);

global $db, $models;
$id = $db->insert($models['board_react'], array(array(
  'board_uri' => $boardUri,
  'name' => $_POST['name'],
  'text' => $_POST['text'],
  'lock_default' => (int)$_POST['lock_default'],
  'hide_default' => (int)$_POST['hide_default'],
  //'image' => $file,
  //'w' => $sizes[0],
  //'h' => $sizes[1],
  //'weight' => 1,
)));

sendResponse(array(
  'id'   => $id,
  //'path' => $file,
));

?>
