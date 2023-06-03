<?php

// used by settings forms and posts
// make sure tmp is made
if (!file_exists('storage/tmp')) {
  return sendResponse(array(), 400, 'Backend server is not ready for files');
}
if (!isset($_FILES['files'])) {
  return sendResponse(array(), 400, 'no file upload set in files field');
}
$hash = hash_file('sha256', $_FILES['files']['tmp_name']);
// would be nice if this wasn't in the webrooot
move_uploaded_file($_FILES['files']['tmp_name'], 'storage/tmp/'.$hash);
$data = array(
  'type' => $_FILES['files']['type'],
  'name' => $_FILES['files']['name'],
  'size' => $_FILES['files']['size'],
  'hash' => $hash,
);
sendResponse($data);