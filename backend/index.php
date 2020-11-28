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

include 'lib/lib.modules.php'; // module functions
include 'lib/modules.php'; // module class
// pipelines
// - boardDB to API
// - thread to API
// - post to API
// - user to API
// - create thread
// - create reply
// - upload file
// - get ip
// - post var processing
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

// build modules...
enableModulesType('models'); // bring models online

include 'lib/lib.board.php';
include 'interfaces/boards.php';
include 'interfaces/posts.php';
include 'interfaces/users.php';
include 'interfaces/files.php';

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

$router->all('/4chan/*', $routers['4chan']);
$router->all('/lynx/*', $routers['lynx']);
$router->all('/opt/*', $routers['opt']);

$router->exec(getServerField('REQUEST_METHOD', 'GET'), getServerField('PATH_INFO'));

?>