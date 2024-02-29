<?php

// require image with each thread
if (!hasPostVars(array('boardUri', 'files'))) {
  // hasPostVars already outputs
  return; //sendResponse(array(), 400, 'Requires boardUri and files');
}
$user_id = (int)getUserID();
$boardUri = $_POST['boardUri'];
$threadid = 0;
$post = array(
  // captcha, spoiler (bool), flag (id)
  'threadid' => $threadid,
  'resto' => 0,
  'name' => getOptionalPostField('name'),
  'sub'  => getOptionalPostField('subject'),
  'com'  => getOptionalPostField('message'),
  'sticky' => 0,
  'closed' => 0,
  'trip' => '', // role is not a tripcode
  'capcode' => getOptionalPostField('role'), // usually #rs off name...
  'country' => '',
  'deleted' => 0,
);
$privPost = array(
  'ip' => getip(),
  'email' => getOptionalPostField('email'),
  // should we write '' if it's empty...
  // just don't allow '' to be deleted...
  'password' => md5(BACKEND_KEY . getOptionalPostField('password')),
);
$data = precreatePost($boardUri, $post, $_POST['files'], $privPost);
// the only 2 lyxnchan valid responses afaik
// need to dig into the source... (api has a source, form returns html)
if (isset($data['id'])) {
  echo $data['id'];
  return;
}
// issue could be outputted as a string
sendJson(json_encode($data));