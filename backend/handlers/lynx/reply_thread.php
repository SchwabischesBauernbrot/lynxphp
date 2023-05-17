<?php

global $db, $models, $now;
if (!hasPostVars(array('boardUri', 'threadId'))) {
  // hasPostVars already outputs
  return; //sendResponse(array(), 400, 'Requires boardUri and threadId');
}
$user_id = (int)getUserID();
$boardUri = $_POST['boardUri'];
$posts_model = getPostsModel($boardUri);
$threadid = (int)$_POST['threadId'];

$post = array(
  // captcha
  // noFlag, email, password (we have this...), spoiler, flag
  'threadid' => $threadid,
  'resto' => 0,
  'name' => getOptionalPostField('name'),
  'sub'  => getOptionalPostField('subject'),
  'com'  => getOptionalPostField('message'),
  // FIXME: hash this...
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

// FIXME: is board locked?
// is thread locked?
global $pipelines;
$reply_allowed_io = array(
  'p'       => $post,
  'allowed' => true,
);
$pipelines[PIPELINE_REPLY_ALLOWED]->execute($reply_allowed_io);

if (!$reply_allowed_io['allowed']) {
  return sendResponse(array(), 200, 'Reply is not allowed');
}

// FIXME: make sure threadId exists...

$newpost_process_io = array(
  'boardUri'     => $boardUri,
  'p'            => $post,
  'priv'         => $privPost,
  'files'        => $files,
  'addToPostsDB' => true,
  'processFilesDB' => true,
  'bumpBoard' => true,
  'bumpThread' => true,
  'returnId' => true,
  'issues'   => array(),
  'createPostOptions' => array(),
);
$pipelines[PIPELINE_NEWPOST_PROCESS]->execute($newpost_process_io);

if ($newpost_process_io['addToPostsDB']) {
  $post = $newpost_process_io['p']; // update post
  $privPost = $newpost_process_io['priv']; // update privPost
  $files = $newpost_process_io['files']; // update files

  // can be an array (issues,id) if file errors
  $data = createPost($boardUri, $post, $files, $privPost, $newpost_process_io['createPostOptions']);

  // issues are usually file upload problems...
  if (empty($data['issues']) && !empty($data['id'])) {
    // bump thread
    $threadid = $post['threadid'];
    if ($threadid) {
      $posts_model = getPostsModel($boardUri);
      // FIXME: sage processing
      // at least make it hoookable
      // bump thread
      $urow = array();
      $db->update($posts_model, $urow, array('criteria'=>array(
        array('postid', '=', $threadid),
      )));
    }
  }

  sendResponse($data);
} else {
  $data = $newpost_process_io['returnId'];
  // inject error messages
  if (count($newpost_process_io['issues'])) {
    $data['issues'] = $newpost_process_io['issues'];
  }
  sendResponse($data);
}