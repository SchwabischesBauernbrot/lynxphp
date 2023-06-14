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
  'password' => md5(BACKEND_KEY . getOptionalPostField('password')),
  'sticky' => 0,
  'closed' => 0,
  'trip' => '', // role is not a tripcode
  'capcode' => getOptionalPostField('role'),
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
  'p'        => $post,
  'boardUri' => $boardUri,
  'allowed'  => true,
  'issues'   => array(),
);
$pipelines[PIPELINE_REPLY_ALLOWED]->execute($reply_allowed_io);

if (!$reply_allowed_io['allowed']) {
  // maybe a 400 is more appropriate
  return sendResponse2(array(), array('code' => 200, 'err' => 'Reply is not allowed: '.join("\n", $issues)));
}

// FIXME: make sure threadId exists...

$newpost_process_io = array(
  'boardUri'     => $boardUri,
  'p'            => $post,
  'priv'         => $privPost,
  'files'        => $files,
  'addToPostsDB' => true,
  'processFilesDB' => true,
  'bumpThread' => true,
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

  // do we bump the thread?
  $threadid = $post['threadid'];
  // issues are usually file upload problems...
  $hasId = !empty($data['id']);
  $notSage = empty($newpost_process_io['createPostOptions']['sage']);
  //echo "[$hasId][$threadid]bump[", $newpost_process_io['bumpThread'], "][$notSage]<br>\n";
  if ($hasId && $threadid && $newpost_process_io['bumpThread'] && $notSage) {
    // bump thread
    $posts_model = getPostsModel($boardUri);
    $urow = array();
    //echo "bumping [$threadid]<br>\n";
    $db->update($posts_model, $urow, array('criteria'=>array(
      array('postid', '=', $threadid),
    )));
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