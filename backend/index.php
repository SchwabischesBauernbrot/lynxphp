<?php

// REST API

require '../common/lib.loader.php';
ldr_require('../common/common.php');
ldr_require('../common/lib.http.server.php');

// read backend config
include 'config.php';

// if OPTIONS do CORS

// message queue

$router = include 'router.php';

// no syscalls needed to get the current time
$now = $_SERVER['REQUEST_TIME_FLOAT'];

// connect to db
include 'lib/database_drivers/' . DB_DRIVER . '.php';
$driver_name = DB_DRIVER . '_driver';
$db = new $driver_name;

// FIXME: we need wrapper functions because, these should just be singleton/globals

// make a queue
// don't auto-detect, just get configuration
// FIXME: make configurable
include '../common/queue_implementations/db.php';
$queue_type_class = 'db' . '_queue_driver';
$queue = new $queue_type_class;

// set up workqueue
include '../common/workqueue.php';
$workqueue = new work_queue;

// set up cache tracker
include 'lib/lib.cache_tracker.php';
$cache_tracker = new cache_tracker;

$tpp = 10; // threads per page

if (!$db->connect_db(DB_HOST, DB_USER, DB_PWD, DB_NAME)) {
  exit(1);
}
// maybe don't output SQL if devmode is off

include '../common/lib.modules.php'; // module functions and classes
// transformations (x => y)
// access list (remove this, add this)
// change input, output (aren't these xforms tho)
// change processing is a little more sticky...


// have to be defined before we can enable modules:
// routers, db options, cache options, pipelines...

// build modules...
enableModulesType('models'); // bring models online

include 'interfaces/requests.php';
// we have database connections
logRequest(getip());

include '../common/lib.pipeline.php';
include 'pipelines.php';

// we map where the code for each route is
// maybe we should inline all those routes here... (avoid 3 file reads)
// or just load one on demand...
$routeConfig = array(
  // name => file
  '4chan' => '4chan',
  'lynx'  => 'lynxchan_minimal',
  'opt'   => 'opt',
);

function buildRouters($routeConfig) {
  $routers = array();
  foreach($routeConfig as $n => &$f) {
    $router = &$routers[$n];
    $router = new BackendRouter;
    // we could put them all in one group
    $router->import(include 'routes/' . $f . '.php');
    unset($router);
  }
  unset($f); // break link
  return $routers;
}

$routers = buildRouters($routeConfig);

include 'lib/lib.board.php';
include 'lib/middlewares.php';
include 'interfaces/boards.php';
include 'interfaces/posts.php';
include 'interfaces/users.php';
include 'interfaces/files.php';
include 'interfaces/sessions.php';
include 'interfaces/settings.php';

$router->all('/4chan/*', $routers['4chan']);
$router->all('/lynx/*', $routers['lynx']);
$router->all('/opt/*', $routers['opt']);

$req_method = getServerField('REQUEST_METHOD', 'GET');
$req_path   = getServerField('PATH_INFO');

registerPackages();
// build routes (and activate backend_handlers.php/models.php)
foreach($packages as $pkg) {
  $pkg->buildBackendRoutes();
}

// we could validate request before bothering the db
if (0) {
  // saves about 40ms
  // but all the pipelines aren't set up

  $res = $router->exec($req_method, $req_path);
  if ($res) {
    exit();
  }
}

$db->ensureTables();

$response_template = array(
  'meta' => array(
    'code' => 200,
  ),
  'data' => array(
  ),
);

function sendResponse2($data, $options = array()) {
  global $response_template, $now;

  // unpack options
  extract(ensureOptions(array(
    'code'  => 200,
    'err'   => '',
    'mtime' => $now,
    'meta'  => array(),
  ), $options));

  // array is copied here?
  $resp = $response_template;
  $resp['meta']['code'] = $code;
  foreach($meta as $k => $v) {
    $resp['meta'][$k] = $v;
  }
  $resp['data'] = $data;
  if ($err) {
    $resp['meta']['err'] = $err;
  }
  if (getQueryField('prettyPrint')) {
    $output = '<pre>' . json_encode($resp, JSON_PRETTY_PRINT) . "</pre>\n";
  } else {
    $output = json_encode($resp);
  }
  // you'd have to be able to calculate the output size
  // on a 304 check
  //$filesize = strlen($output);
  _doHeaders($mtime, array('contentType' => 'application/json'));
  echo $output;
  return true;
}

function sendRawResponse($mixed, $code = 200, $err = '') {
  if ($code !== 200) http_response_code($code);
  if (getQueryField('prettyPrint')) {
    echo '<pre>', json_encode($mixed, JSON_PRETTY_PRINT), "</pre>\n";
  } else {
    echo json_encode($mixed);
  }
  return true;
}

function sendResponse($data, $code = 200, $err = '', $meta = false) {
  global $response_template;
  $resp = $response_template;
  $resp['meta']['code'] = $code;
  if ($meta) {
    foreach($meta as $k => $v) {
      $resp['meta'][$k] = $v;
    }
  }
  $resp['data'] = $data;
  if ($err) {
    $resp['meta']['err'] = $err;
  }
  return sendRawResponse($resp, $code, $err);
}

// wrapper for now
function wrapContent($error) {
  sendResponse(array(), 400, $error);
}

//echo "method[$req_method]<br>\n";

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