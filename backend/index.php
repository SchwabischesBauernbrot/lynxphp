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
$routers['4chan'] = include 'apis/4chan.php';
$routers['lynx'] = include 'apis/lynxchan_minimal.php';
$routers['opt'] = include 'apis/opt.php';

// transformations (x => y)
// access list (remove this, add this)
// change input, output
// change processing is a little more sticky...

include 'interfaces/boards.php';
include 'interfaces/posts.php';
include 'interfaces/users.php';

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

$router->all('/4chan/*', $routers['4chan']);
$router->all('/lynx/*', $routers['lynx']);
$router->all('/opt/*', $routers['opt']);

$router->exec(getServerField('REQUEST_METHOD', 'GET'), getServerField('PATH_INFO'));

?>
