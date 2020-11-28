<?php

// REST API

// read backend config
include 'config.php';

// if OPTIONS do CORS

// message queue

include '../common/router.php';
include '../common/post_vars.php';
$router = new Router;

// connect to db
include 'lib/lib.model.php';
// FIXME: database type to select driver
$db_driver = 'mysql';
include 'lib/database_drivers/'.$db_driver.'.php';
$driver_name = $db_driver . '_driver';
$db = new $driver_name;

$tpp = 10; // threads per page

if (!$db->connect_db(DB_HOST, DB_USER, DB_PWD, DB_NAME)) {
  exit();
}

include 'lib/lib.board.php';

// build modules...
include 'lib/lib.modules.php';
enableModulesType('models');

include 'lib/modules.php';
// pipelines
// boardDB to API
// thread to API
// post to API
// user to API
// create thread
// create reply
// upload file
// get ip
// post var processing
$pipelines['boardData'] = new pipeline_registry;
$pipelines['postData'] = new pipeline_registry;
$pipelines['userData'] = new pipeline_registry;
$pipelines['post'] = new pipeline_registry;
$pipelines['file'] = new pipeline_registry;

$routers = array();
$routers['4chan'] = new router;
$routers['lynx'] = new router;
$routers['opt'] = new router;

$pipelines['api_4chan'] = new pipeline_registry;
$pipelines['api_lynx'] = new pipeline_registry;
$pipelines['api_opt'] = new pipeline_registry;

// transformations (x => y)
// access list (remove this, add this)
// change input, output
// change processing is a little more sticky...

include 'interfaces/boards.php';
include 'interfaces/posts.php';
include 'interfaces/users.php';

// https://a.4cdn.org/boards.json
$routers['4chan']->get('/boards.json', function($request) {
  $boards = listBoards();
  echo json_encode($boards);
});

// https://a.4cdn.org/po/catalog.json
$routers['4chan']->get('/:board/catalog.json', function($request) {
  global $tpp;
  $boardUri = $request['params']['board'];
  $threads = boardCatalog($boardUri);
  if (!$threads) {
    sendResponse(array(), 404, 'Board not found');
    return;
  }
  $pages = ceil(count($threads) / $tpp);
  $res = array();
  for($i = 1; $i <= $pages; $i++) {
    $res[] = array(
      'page' => $i,
      'threads' => $threads[$i],
    );
  }
  echo json_encode($res);
});

// FIXME: https://a.4cdn.org/po/threads.json
// FIXME: https://a.4cdn.org/archive.json

// https://a.4cdn.org/po/thread/570368.json
$routers['4chan']->get('/:board/thread/:thread', function($request) {
  $boardUri = $request['params']['board'];
  $threadNum = (int)str_replace('.json', '', $request['params']['thread']);
  $posts = getThread($boardUri, $threadNum);
  echo json_encode(array('posts'=>$posts));
});

// https://a.4cdn.org/po/2.json
$routers['4chan']->get('/:board/:page', function($request) {
  $boardUri = $request['params']['board'];
  $page = str_replace('.json', '', $request['params']['page']);
  $threads = boardPage($boardUri, $page);
  echo json_encode($threads);
});

$response_template = array(
  'meta' => array(
    'code' => 200,
  ),
  'data' => array(
  ),
);

function sendResponse($data, $code = 200, $err = '') {
  global $response_template;
  $resp = $response_template;
  $resp['meta']['code'] = $code;
  $resp['data'] = $data;
  if ($err) {
    $resp['meta']['err'] = $err;
  }
  echo json_encode($resp);
  return true;
}

function getUserID() {
  global $db, $models;
  $sid = empty($_SERVER['HTTP_SID']) ? '' : $_SERVER['HTTP_SID'];
  $sesRes = $db->find($models['session'], array('criteria' => array(
    array('session', '=', $sid),
  )));
  if (!$db->num_rows($sesRes)) {
    return null;
  }
  $sesRow = $db->get_row($sesRes);
  if (time() > $sesRow['expires']) {
    return false;
  }
  return $sesRow['user_id'];
}

function loggedIn() {
  $userid = getUserID();
  if ($userid === null) {
    // session does not exist
    sendResponse(array(), 401, 'Invalid Session');
    return;
  }
  if ($userid === false) {
    // expired
    sendResponse(array(), 401, 'Invalid Session');
    return;
  }
  return $userid;
}

function processFiles($boardUri, $files_json, $threadid, $postid) {
  $files = json_decode($files_json, true);
  if (!is_array($files)) {
    return;
  }
  global $db;
  $post_files_model = getPostFilesModel($boardUri);
  foreach($files as $num => $file) {
    // move file into path
    $srcPath = 'storage/tmp/'.$file['hash'];
    if (!file_exists($srcPath)) {
      continue;
    }
    $threadPath = 'storage/boards/' . $boardUri . '/' . $threadid;
    if (!file_exists($threadPath)) {
      mkdir($threadPath);
    }
    $arr = explode('.', $file['name']);
    $ext = end($arr);
    $finalPath = $threadPath . '/' . $postid . '_' . $num . '.' . $ext;
    // not NFS safe
    rename($srcPath, $finalPath);
    $db->insert($post_files_model, array(array(
      'postid' => $postid,
      'sha256' => $file['hash'],
      'path'   => $finalPath,
      'ext'    => $ext,
      'browser_type' => $file['type'],
      'filename'     => $file['name'],
      'w' => 0,
      'h' => 0,
      'filedeleted' => 0,
      'spoiler' => 0,
    )));
  }
}

//
// Lynxchan compatible API for lynxphp
//

$routers['lynx']->post('/registerAccount', function($request) {
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

$routers['lynx']->post('/login', function($request) {
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

$routers['lynx']->post('/createBoard', function($request) {
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

$routers['lynx']->post('/files', function($request) {
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

$routers['lynx']->post('/newThread', function($request) {
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

$routers['lynx']->post('/replyThread', function($request) {
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

$routers['lynx']->get('/account', function($request) {
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  $userRes = getAccount($user_id);
  $ownedBoards = userBoards($user_id);
  echo json_encode(array(
    'noCaptchaBan' => false,
    'login' => $userRes['username'],
    'email' => $userRes['email'],
    'globalRole' => 99,
    //'disabledLatestPostings'
    //'volunteeredBoards'
    'boardCreationAllowed' => true,
    'ownedBoards' => $ownedBoards,
    //'settings'
    'reportFilter' => array(), // category filters for e-mail notifications
  ));
});

//
// Optimized routes for lynxphp
//

$routers['opt']->get('/session', function($request) {
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  sendResponse(array('session' => 'ok'));
});

$routers['opt']->get('/boards/:uri/:page', function($request) {
  global $tpp;

  $boardUri = $request['params']['uri'];
  $pageNum = $request['params']['page'] ? (int)$request['params']['page'] : 1;

  $boardData = getBoard($boardUri);
  if (!$boardData) {
    return sendResponse(array(), 404, 'Board does not exist');
  }
  boardDBtoAPI($boardData);
  $threadCount = getThreadCount($boardUri);
  $threads = boardPage($boardUri, $pageNum);
  sendResponse(array(
    'board' => $boardData,
    'page1' => $threads,
    'threadsPerPage'   => $tpp,
    'threadCount' => $threadCount,
    'pageCount' => ceil($threadCount/$tpp),
  ));
});

$routers['opt']->get('/myBoards', function($request) {
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  $boards = userBoards($user_id);
  sendResponse($boards);
});

// non-standard 4chan api - lets disable for now
// /opt should have replaced this
$routers['opt']->get('/:board', function($request) {
  global $db, $models;
  $boardUri = str_replace('.json', '', $request['params']['board']);
  $boardData = getBoard($boardUri);
  if (!$boardData) {
    echo '[]';
    return;
  }
  echo json_encode($boardData);
});


$router->all('/4chan/*', $routers['4chan']);
$router->all('/lynx/*', $routers['lynx']);
$router->all('/opt/*', $routers['opt']);

$router->exec(getServerField('REQUEST_METHOD', 'GET'), getServerField('PATH_INFO'));

?>
