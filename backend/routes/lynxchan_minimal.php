<?php

//
// Lynxchan compatible API for lynxphp
//

$router = new router;

$router->post('/registerAccount', function($request) {
  if (!hasPostVars(array('login', 'password', 'email'))) {
    // hasPostVars already outputs
    return; // sendResponse(array(), 400, 'Needs login, password, and email');
  }
  global $db, $models;
  $email = strtolower($_POST['email']);
  $login = strtolower($_POST['login']);
  $emRes = $db->find($models['user'], array('criteria' => array(
    array('email', '=', $email),
  )));
  if ($db->num_rows($emRes)) {
    return sendResponse(array(), 403, 'Already has account');
  }
  $res = $db->find($models['user'], array('criteria' => array(
    array('username', '=', $login),
  )));
  if ($db->num_rows($res)) {
    return sendResponse(array(), 403, 'Already Taken');
  }
  //echo "Creating<br>\n";
  $id = $db->insert($models['user'], array(array(
    'username' => $login,
    'email'    => $email,
    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
  )));
  $data = array('id'=>$id);
  sendResponse($data);
});

$router->post('/login', function($request) {
  global $db, $models;
  // login, password, remember
  if (!hasPostVars(array('login', 'password'))) {
    // hasPostVars already outputs
    return; // sendResponse(array(), 400, 'Requires login and password');
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

  $sesRow = getSession();
  if ($sesRow) {
    if ($sesRow['userid']) {
      return sendResponse(array(), 500, 'Already logged in');
    } else {
      // upgrade session
      if (!sessionSetUserID($sesRow['session'], $row['userid'])) {
        return sendResponse(array(), 500, 'Could not upgrade session');
      }
      $ses['session'] = $sesRow['session'];
      $ses['ttl'] = $sesRow['expires'];
    }
  } else {
    // we should create a session token for this user
    $ses = createSession($row['userid']);
    if (!$ses) {
      return sendResponse(array(), 500, 'Could not create session');
    }
  }

  // and return it
  $data = array(
    'username' => $row['username'],
    'session'  => $ses['session'],
    'ttl'      => $ses['ttl'],
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
    // hasPostVars already outputs
    return; // sendResponse(array(), 400, 'Requires boardUri, boardName and boardDescription');
  }
  $boardUri = strtolower($_POST['boardUri']);
  $res = $db->find($models['board'], array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));
  if ($db->num_rows($res)) {
    return sendResponse(array(), 403, 'Board already exists');
  }
  $fupPath = 'storage/boards/' . $boardUri;
  if (!file_exists($fupPath) && !@mkdir($fupPath)) {
    return sendResponse(array(), 500, 'Can not create board directory for file uploads');
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

// used by settings forms and posts
$router->post('/files', function($request) {
  // make sure tmp is made
  if (!file_exists('storage/tmp')) {
    return sendResponse(array(), 400, 'Backend server is not ready for files');
  }
  if (!isset($_FILES['files'])) {
    return sendRepsonse(array(), 400, 'no file upload set in files field');
  }
  $hash = hash_file('sha256', $_FILES['files']['tmp_name']);
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
    // hasPostVars already outputs
    return; //sendResponse(array(), 400, 'Requires boardUri and files');
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
    'password' => getOptionalPostField('password'),
    'sticky' => 0,
    'closed' => 0,
    'trip' => '',
    'capcode' => '',
    'country' => '',
    'deleted' => 0,
  )));
  processFiles($boardUri, $_POST['files'], $id, $id);
  $data = $id;
  sendResponse($data);
});

$router->post('/replyThread', function($request) {
  global $db;
  if (!hasPostVars(array('boardUri', 'threadId'))) {
    // hasPostVars already outputs
    return; //sendResponse(array(), 400, 'Requires boardUri and threadId');
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
    'password' => getOptionalPostField('password'),
    'sticky' => 0,
    'closed' => 0,
    'trip' => '',
    'capcode' => '',
    'country' => '',
    'deleted' => 0,
  )));
  $data = $id;
  $issues = processFiles($boardUri, $_POST['files'], $threadid, $id);

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
