<?php

global $db, $models, $now;
// require image with each thread
if (!hasPostVars(array('boardUri', 'files'))) {
  // hasPostVars already outputs
  return; //sendResponse(array(), 400, 'Requires boardUri and files');
}
$user_id = (int)getUserID();
$boardUri = $_POST['boardUri'];
$threadid = 0;
$post = array(
  // noFlag, email, password, captcha, spoiler, flag
  'threadid' => $threadid,
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
);
$privPost = array(
  'ip' => getip(),
);
$files = $_POST['files'];

// tag post
$post['tags'] = tagPost($boardUri, $post, $files, $privPost);

global $pipelines;
$newpost_process_io = array(
  'boardUri'     => $boardUri,
  'p'            => $post,
  'priv'         => $privPost,
  'files'        => $files,
  'addToPostsDB' => true,
  'processFilesDB' => true,
  'bumpThread' => false, // doesn't do anything for a new thread...
  'returnId' => true,
  'issues'   => array(),
  'createPostOptions' => array('bumpBoard' => true),
);
$pipelines[PIPELINE_NEWPOST_PROCESS]->execute($newpost_process_io);

if ($newpost_process_io['addToPostsDB']) {
  $post = $newpost_process_io['p']; // update post
  $privPost = $newpost_process_io['priv']; // update privPost
  $files = $newpost_process_io['files']; // update files

  // can be an array (issues,id) if file errors
  $data = createPost($boardUri, $post, $files, $privPost, $newpost_process_io['createPostOptions']);

  $noIssues = empty($data['issues']);
  if (!$noIssues) {
    return sendResponse2($data);
  }

  sendResponse2($data['id']);
} else {
  $data = $newpost_process_io['returnId'];
  // inject error messages
  if (count($newpost_process_io['issues'])) {
    $data['issues'] = $newpost_process_io['issues'];
  }
  sendResponse2($data);
}