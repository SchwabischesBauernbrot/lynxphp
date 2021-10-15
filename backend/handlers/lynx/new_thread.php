<?php

global $db, $models, $now;
// require image with each thread
if (!hasPostVars(array('boardUri', 'files'))) {
  // hasPostVars already outputs
  return; //sendResponse(array(), 400, 'Requires boardUri and files');
}
$user_id = (int)getUserID();
$boardUri = $_POST['boardUri'];
$posts_model = getPostsModel($boardUri);
$id = $db->insert($posts_model, array(array(
  // noFlag, email, password, captcha, spoiler, flag
  'threadid' => 0,
  'resto' => 0,
  'name' => getOptionalPostField('name'),
  'sub'  => getOptionalPostField('subject'),
  'com'  => getOptionalPostField('message'),
  'password' => getOptionalPostField('password'),
  'sticky' => 0,
  'closed' => 0,
  'trip' => '',
  'capcode' => '',
  'country' => '',
  'deleted' => 0,
)));
processFiles($boardUri, $_POST['files'], $id, $id);

// bump board
$inow = (int)$now;
$urow = array('last_thread' => $inow, 'last_post' => $inow);
$db->update($models['board'], $urow, array('criteria'=>array(
  array('uri', '=', $boardUri),
)));

$data = (int)$id;
sendResponse($data);