<?php

// REST API

// read backend config
include 'config.php';

// if OPTIONS do CORS

// message queue

$router = include '../common/router.php';
include '../common/post_vars.php';

// connect to db
// FIXME: database type to select driver
$db_driver = 'mysql';
include 'lib/database_drivers/'.$db_driver.'.php';
$driver_name = $db_driver . '_driver';
$db = new $driver_name;

$tpp = 10; // threads per page

if (!$db->connect_db(DB_HOST, DB_USER, DB_PWD, DB_NAME)) {
  exit();
}

include '../common/lib.modules.php'; // module functions and classes
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
$routers['4chan'] = include 'routes/4chan.php';
$routers['lynx']  = include 'routes/lynxchan_minimal.php';
$routers['opt']   = include 'routes/opt.php';

// transformations (x => y)
// access list (remove this, add this)
// change input, output (aren't these xforms tho)
// change processing is a little more sticky...


// have to be defined before we can enable modules:
// routers, db options, cache options, pipelines...

// build modules...
enableModulesType('models'); // bring models online

include 'lib/lib.board.php';
include 'lib/middlewares.php';
include 'interfaces/boards.php';
include 'interfaces/posts.php';
include 'interfaces/users.php';
include 'interfaces/files.php';
include 'interfaces/sessions.php';

include '../common/modules/board/banners/models.php';
include '../common/modules/board/banners/backend_handlers.php';

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

// wrapper for now
function wrapContent($error) {
  sendResponse(array(), 400, $error);
}

$router->all('/4chan/*', $routers['4chan']);
$router->all('/lynx/*', $routers['lynx']);
$router->all('/opt/*', $routers['opt']);

$router->exec(getServerField('REQUEST_METHOD', 'GET'), getServerField('PATH_INFO'));

?>
