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
  'ip' => getip(),
);

global $pipelines;
$newpost_process_io = array(
  'p'            => $post,
  'files'        => $_POST['files'],
  'boardUri'     => $boardUri,
  'addToPostsDB' => true,
  'processFilesDB' => true,
  'bumpBoard' => true,
  'bumpThread' => true,
  'returnId' => true,
);
$pipelines[PIPELINE_NEWPOST_PROCESS]->execute($newpost_process_io);

if ($newpost_process_io['addToPostsDB']) {
  $post = $newpost_process_io['p']; // update post

  $posts_model = getPostsModel($boardUri);
  $id = $db->insert($posts_model, array($post));
  processFiles($boardUri, $_POST['files'], $id, $id);

  // bump board
  $inow = (int)$now;
  $urow = array('last_thread' => $inow, 'last_post' => $inow);
  $db->update($models['board'], $urow, array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));

  $data = (int)$id;
  sendResponse($data);
} else {
  sendResponse($newpost_process_io['returnId']);
}