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
  $posts_model = getPostsModel($boardUri);
  $id = $db->insert($posts_model, array($post));
  $posts_priv_model = getPrivatePostsModel($boardUri);
  $privPost['postid'] = $id; // update postid
  $db->insert($posts_priv_model, array($privPost));
  $issues = processFiles($boardUri, $_POST['files'], $id, $id);

  // bump board
  $inow = (int)$now;
  $urow = array('last_thread' => $inow, 'last_post' => $inow);
  $db->update($models['board'], $urow, array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));

  $data = (int)$id;
  if (count($issues)) {
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