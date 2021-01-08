<?php

// REST API

include '../common/post_vars.php';

// read backend config
include 'config.php';

// if OPTIONS do CORS

// message queue

$router = include '../common/router.php';

// connect to db
include 'lib/database_drivers/' . DB_DRIVER . '.php';
$driver_name = DB_DRIVER . '_driver';
$db = new $driver_name;

$tpp = 10; // threads per page

if (!$db->connect_db(DB_HOST, DB_USER, DB_PWD, DB_NAME)) {
  exit(1);
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
definePipeline('PIPELINE_BOARD_DATA', 'boardData');
definePipeline('PIPELINE_POST_DATA',  'postData');
definePipeline('PIPELINE_USER_DATA',  'userData');
definePipeline('PIPELINE_POST', 'post');
definePipeline('PIPELINE_FILE', 'file');

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

$packages = array();
registerPackageGroup('board');
registerPackageGroup('post');
registerPackageGroup('user');
// build routes (and activate backend_handlers.php/models.php)
foreach($packages as $pkg) {
  $pkg->buildBackendRoutes();
}

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

$req_method = getServerField('REQUEST_METHOD', 'GET');
$req_path   = getServerField('PATH_INFO');

$res = $router->exec($req_method, $req_path);
if (!$res) {
  http_response_code(404);
  sendResponse(array(
    'method' => $req_method,
    'path'   => $req_path,
    'routes' => $router->debug($req_method),
  ), 404, 'route not found');
  //echo "<h1>404 route not found</h1>";
  //echo "METHOD: ", getServerField('REQUEST_METHOD', 'GET'), "<br>\n";
  //echo "PATH: ", getServerField('PATH_INFO'), "<br>\n";
  //die();
}

?>
