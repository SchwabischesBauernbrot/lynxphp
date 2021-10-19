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

// is board locked?
global $pipelines;
$reply_allowed_io = array(
  'p'       => $post,
  'allowed' => true,
);
$pipelines[PIPELINE_REPLY_ALLOWED]->execute($reply_allowed_io);

if (!$reply_allowed_io['allowed']) {
  return sendResponse(array(), 200, 'Reply is not allowed');
}

// make sure threadId exists...
$id = $db->insert($posts_model, array($post));
$data = (int)$id;
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

if (count($issues)) {
  return sendResponse(array(
    'issues' => $issues,
    'id' => $data
  ));
}

sendResponse($data);