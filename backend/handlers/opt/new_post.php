<?php

global $db, $models;
if (!hasPostVars(array('boardUri', 'threadId'))) {
  // hasPostVars already outputs
  return; //sendResponse(array(), 400, 'Requires boardUri and threadId');
}
$user_id = (int)getUserID();
$boardUri = $_POST['boardUri'];
$posts_model = getPostsModel($boardUri);
// optional
$threadid = (int)getOptionalPostField('threadId'); // 0 means new thread

$post = array(
  // noFlag, email, captcha, spoiler, flag
  'threadid' => $threadid,
  'resto' => 0,
  'name' => getOptionalPostField('name'),
  'sub'  => getOptionalPostField('subject'),
  'com'  => getOptionalPostField('message'),
  'sticky' => 0,
  'closed' => 0,
  'trip' => '', // role is not a tripcode
  'capcode' => getOptionalPostField('role'),
  'country' => '',
  'deleted' => 0,
);
$privPost = array(
  'ip' => getip(),
  'email' => getOptionalPostField('email'),
  'password' => md5(BACKEND_KEY . getOptionalPostField('password')),
);

$data = precreatePost($boardUri, $post, $_POST['files'], $privPost);
sendResponse2($data);