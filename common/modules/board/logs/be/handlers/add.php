<?php
$params = $get();

$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return;
}
global $db, $models;
// FIXME: validation that there is an upload...
// handle file uploads...
$bannersDir = 'storage/boards/' . $boardUri . '/banners';
if (!file_exists($bannersDir)) {
  mkdir($bannersDir);
}
$file = $bannersDir . '/' . basename($_FILES['files']['tmp_name']);
move_uploaded_file($_FILES['files']['tmp_name'], $file);

$boardData = getBoardByUri($boardUri);
$sizes = getimagesize($file);

$id = $db->insert($models['board_banner'], array(array(
  'board_id' => $boardData['boardid'],
  'image' => $file,
  'w' => $sizes[0],
  'h' => $sizes[1],
  'weight' => 1,
)));

sendResponse(array(
  'id'   => $id,
  'path' => $file,
));

?>
