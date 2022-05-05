<?php

// FIXME: we need access to package
$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;
// validate data
// handle file uploads...
$fileField = 'image';
if (!isset($_FILES) || !isset($_FILES[$fileField])) {
  // reload form?
  return wrapContent('An image is required');
}
if (is_array($_FILES[$fileField]['tmp_name'])) {
  return wrapContent("Write me!<br>\n");
} else {
  // could run make_file($tmpfile, $type, $filename) here...
  $files = array(array($_FILES[$fileField]['tmp_name'], $_FILES[$fileField]['type'], $_FILES[$fileField]['name']));
}

// call backend handler to add banner
$result = $pkg->useResource('add', array(
  'boardUri' => $boardUri,
  'files'    => make_file($files[0][0], $files[0][1], $files[0][2]),
));

if (isset($result['id'])) {
  // success
  global $BASE_HREF;
  redirectTo($BASE_HREF . $boardUri . '/settings/banners.html');
} else {
  wrapContent('Something went wrong...');
}

?>
