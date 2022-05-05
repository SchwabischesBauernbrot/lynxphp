<?php

global $db, $models;
// boardUri, boardName, boardDescription, session
$user_id = loggedIn();
if (!$user_id) {
  return;
}
if (!hasPostVars(array('boardUri', 'boardName', 'boardDescription'))) {
  // hasPostVars already outputs
  return; // sendResponse(array(), 400, 'Requires boardUri, boardName and boardDescription');
}
$boardUri = strtolower($_POST['boardUri']);

// RFC1738: a-z0-9 $-_.~+!*'(),
// RFC3986: a-z0-9 -_.~
// now reserved: :/?#[]@!$&'()*+,;=
// {}^\~ are unsafe
// but some can be urlencoded...
// _ takes a shift and we don't need another separator like -
// ~ takes a shift but also unsafe...
// - is not allowed in postgres table names...
// postgres allows a-z ( also letters with diacritical marks and non-Latin letters)
// _$[0-9]
// $ aren't SQL standard
// mysql [0-9a-zAz]$_
$allowedChars = array('-', '.');
for($p = 0; $p < strlen($boardUri); $p++) {
  if (preg_match('/^[a-z0-9]$/', $boardUri[$p])) {
    // allowed
    continue;
  }
  if (!in_array($boardUri[$p], $allowedChars)) {
    // not allowed
    return sendResponse(array(), 400, 'boardUri has invalid characters: [' . $boardUri[$p] . ']'. $boardUri);
  }
}

$res = $db->find($models['board'], array('criteria'=>array(
  array('uri', '=', $boardUri),
)));
if ($db->num_rows($res)) {
  return sendResponse(array(), 403, 'Board already exists');
}
$fupPath = 'storage/boards/' . $boardUri;
if (!file_exists($fupPath) && !@mkdir($fupPath)) {
  return sendResponse(array(), 500, 'Can not create board directory for file uploads');
}

// FIXME check unique fields...
$db->insert($models['board'], array(array(
  'uri'         => $boardUri,
  'title'       => $_POST['boardName'],
  'description' => $_POST['boardDescription'],
  'owner_id'    => $user_id,
)));
$data = 'ok';
sendResponse($data);