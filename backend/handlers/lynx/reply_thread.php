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
);
$pipelines[PIPELINE_NEWPOST_PROCESS]->execute($newpost_process_io);

if ($newpost_process_io['addToPostsDB']) {
  $post = $newpost_process_io['p']; // update post
  $privPost = $newpost_process_io['priv']; // update privPost
  $files = $newpost_process_io['files']; // update files

  // can be an array (issues,id) if file errors
  $data = createPost($boardUri, $post, $files, $privPost);

  /*
  $id = $db->insert($posts_model, array($post));
  $data = (int)$id;
  $posts_priv_model = getPrivatePostsModel($boardUri);
  $privPost['postid'] = $id; // update postid
  $db->insert($posts_priv_model, array($privPost));
  $issues = processFiles($boardUri, $_POST['files'], $threadid, $id);

  // bump board
  $urow = array('last_post' => (int)$now);
  $db->update($models['board'], $urow, array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));

  // bump thread
  $urow = array();
  $db->update($posts_model, $urow, array('criteria'=>array(
    array('postid', '=', $threadid),
  )));

  if (is_array($issues)) {
    return sendResponse(array(
      'issues' => $issues,
      'id' => $data
    ));
  }
  */

  sendResponse($data);
} else {
  sendResponse($newpost_process_io['returnId']);
}