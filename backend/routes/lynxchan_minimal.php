<?php

//
// Lynxchan compatible API for lynxphp
//

$router = new router;

$router->post('/registerAccount', function($request) {
  global $db, $models;
  if (!hasPostVars(array('login', 'password', 'email'))) {
    return;
  }
  $emRes = $db->find($models['user'], array('criteria' => array(
    array('email', '=', $_POST['email']),
  )));
  if ($db->num_rows($emRes)) {
    return sendResponse(array(), 403, 'Already has account');
    return;
  }
  $res = $db->find($models['user'], array('criteria' => array(
    array('username', '=', $_POST['login']),
  )));
  if ($db->num_rows($res)) {
    return sendResponse(array(), 403, 'Already Taken');
    return;
  }
  //echo "Creating<br>\n";
  $id = $db->insert($models['user'], array(array(
    'username' => $_POST['login'],
    'email'    => $_POST['email'],
    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
  )));
  $data = array('id'=>$id);
  sendResponse($data);
});

$router->post('/login', function($request) {
  global $db, $models;
  // login, password, remember
  if (!hasPostVars(array('login', 'password'))) {
    return;
  }
  $res = $db->find($models['user'], array('criteria' => array(
    array('username', '=', $_POST['login']),
  )));
  if (!$db->num_rows($res)) {
    return sendResponse(array(), 401, 'Incorrect login - no username');
  }
  $row = $db->get_row($res);
  $db->free($res);
  // password check
  if (!password_verify($_POST['password'], $row['password'])) {
    return sendResponse(array(), 401, 'Incorrect login - bad pass');
  }
  // we should create a session token for this user
  $session = md5(uniqid());
  $ttl = time() + 86400; // 1 day from now
  // FIXME: check to make sure session isn't already used...
  $db->insert($models['session'], array(array(
    'session' => $session,
    'user_id' => $row['userid'],
    'expires' => $ttl,
    'ip'      => getip(),
  )));

  // and return it
  $data = array(
    'username' => $row['username'],
    'session'  => $session,
    'ttl'      => $ttl,
  );
  sendResponse($data);
});

$router->post('/createBoard', function($request) {
  global $db, $models;
  // boardUri, boardName, boardDescription, session
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  if (!hasPostVars(array('boardUri', 'boardName', 'boardDescription'))) {
    return;
  }
  $boardUri = strtolower($_POST['boardUri']);
  $res = $db->find($models['board'], array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));
  if ($db->num_rows($res)) {
    return sendResponse(array(), 403, 'Board already exists');
  }

  // FIXME check unique fields...
  $db->insert($models['board'], array(array(
    'uri'         => $boardUri,
    'title'       => $_POST['boardName'],
    'description' => $_POST['boardDescription'],
    'owner_id'    => $user_id,
  )));
  $data = 'ok';
  sendResponse($data);
});

$router->post('/files', function($request) {
  $hash = hash_file('sha256', $_FILES['files']['tmp_name']);
  // FIXME: make sure tmp is made
  move_uploaded_file($_FILES['files']['tmp_name'], 'storage/tmp/'.$hash);
  $data=array(
    'type' => $_FILES['files']['type'],
    'name' => $_FILES['files']['name'],
    'size' => $_FILES['files']['size'],
    'hash' => $hash,
  );
  sendResponse($data);
});

$router->post('/newThread', function($request) {
  global $db;
  // require image with each thread
  if (!hasPostVars(array('boardUri', 'files'))) {
    return;
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
    'sticky' => 0,
    'closed' => 0,
    'trip' => '',
    'capcode' => '',
    'country' => '',
  )));
  processFiles($boardUri, $_POST['files'], $id, $id);
  $data = $id;
  sendResponse($data);
});

$router->post('/replyThread', function($request) {
  global $db;
  if (!hasPostVars(array('boardUri', 'threadId'))) {
    return;
  }
  $user_id = (int)getUserID();
  $boardUri = $_POST['boardUri'];
  $posts_model = getPostsModel($boardUri);
  $threadid = (int)$_POST['threadId'];
  // make sure threadId exists...
  $id = $db->insert($posts_model, array(array(
    // noFlag, email, password, captcha, spoiler, flag
    'threadid' => $threadid,
    'resto' => 0,
    'name' => getOptionalPostField('name'),
    'sub'  => getOptionalPostField('subject'),
    'com'  => getOptionalPostField('message'),
    'sticky' => 0,
    'closed' => 0,
    'trip' => '',
    'capcode' => '',
    'country' => '',
  )));
  $data = $id;
  // bump thread
  $urow = array('updated_at' => '');
  $db->update($posts_model, $urow, array('criteria'=>array(
    array('postid', '=', $threadid),
  )));
  processFiles($boardUri, $_POST['files'], $threadid, $id);
  sendResponse($data);
});

$router->get('/account', function($request) {
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  $userRes = getAccount($user_id);
  $ownedBoards = userBoards($user_id);
  $groups = getUserGroups($user_id);

  echo json_encode(array(
    'noCaptchaBan' => false,
    'login' => $userRes['username'],
    'email' => $userRes['email'],
    'globalRole' => 99,
    //'disabledLatestPostings'
    //'volunteeredBoards'
    'boardCreationAllowed' => true,
    'ownedBoards' => $ownedBoards,
    'groups' => $groups,
    //'settings'
    'reportFilter' => array(), // category filters for e-mail notifications
  ));
});

return $router;

?>
