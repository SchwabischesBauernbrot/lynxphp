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

// reading from db to save db is it really worth it?
// file might be fine
/*
// connect to scatch
include '../common/scratch_implementations/' . SCRATCH_DRIVER . '.php';
$scratch_type_class = SCRATCH_DRIVER . '_scratch_driver';
$scratch = new $scratch_type_class;
*/

/*
// seems to be similar to table_tracker
// set up cache tracker
include 'lib/lib.cache_tracker.php';
$cache_tracker = new cache_tracker;
*/

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
enableModulesType('models'); // online models from common/modules/base/models.php

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
  'doubleplus' => 'doubleplus',
);

function buildRouters($routeConfig) {
  global $router;

  $routers = array();
  foreach($routeConfig as $n => &$f) {
    $r = &$routers[$n];
    $r = new BackendRouter;
    // we could put them all in one group
    $r->import(include 'routes/' . $f . '.php');
    $router->all('/' . $n . '/'. '*', $r);
    unset($r);
  }
  unset($f); // break link
  return $routers;
}

$routers = buildRouters($routeConfig);

include 'lib/lib.board.php';
include 'lib/lib.ffmpeg.php';
include 'lib/middlewares.php';
include 'interfaces/boards.php';
include 'interfaces/posts.php';
include 'interfaces/replies.php';
include 'interfaces/threads.php';
include 'interfaces/post_tags.php';
include 'interfaces/users.php';
include 'interfaces/files.php';
include 'interfaces/sessions.php';
include 'interfaces/settings.php';

/*
$router->all('/4chan/*', $routers['4chan']);
$router->all('/lynx/*', $routers['lynx']);
$router->all('/opt/*', $routers['opt']);
$router->all('/doubleplus/*', $routers['doubleplus']);
*/

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

ldr_done();

// handle 304
if ($router->sendHeaders($req_method, $req_path)) {
  return; // 304
}

//$txt = $req_method . ' ' . $req_path;
//file_put_contents('log.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);


$response_template = array(
  'meta' => array(
    'code' => 200,
  ),
  'data' => array(
  ),
);

// options
// - code
// - err
// - mtime
// - meta
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
  // probably not the best place for this
  // since request is gone
  // and we want to inject into the query
  // actually the query matters less
  if (isset($_GET['portals'])) {
    //$resp['meta']['portals'] = array();
    global $pipelines;
    $io = array(
      // would be nicer if we had request...
      'data'    => $data,
      'mtime'   => $mtime,
      'err'     => $err,
      'meta'    => $meta,
      'portals' => explode(',', $_GET['portals']),
      'out' => array(),
    );
    if (!isset($io['data']['board'])) {
      // how do we note this to the front end..
      // a header?
      // extra key?
      $data['MISSING_BOARD'] = true;
    }
    $pipelines[PIPELINE_PORTALS_DATA]->execute($io);
    if ($io['out']) {
      //$resp['meta']['portals'] = $io['out'];
      // strict filter
      foreach($io['portals'] as $p) {
        // board
        $resp['meta']['portals'][$p] = isset($io['out'][$p]) ? $io['out'][$p] : '';
      }
    }
    /*
    $resp['meta']['portals'] = array();
    foreach($portals as $p) {
      $io = array(
        'portal' => $p,
      );
      //$pipelines[]->execute($resp['meta']['portals'][$p]);
    }
    */
  }

  // should we only set this if there's actually data?
  // how to tell an array()/false versus no data?
  $resp['data'] = $data;
  if ($err) {
    $resp['meta']['err'] = $err;
  }
  if (getQueryField('prettyPrint')) {
    // we need the HTML for the htmlspecialchars
    // and we need that to stop executing user generate js
    // but isn't it the default?
    //_doHeaders($mtime, array('contentType' => 'text/html'));
    // code needs <br>s
    $output = '<pre>' . htmlspecialchars(json_encode($resp, JSON_PRETTY_PRINT)) . "</pre>\n";
  } else {
    //_doHeaders($mtime, array('contentType' => 'application/json'));
    $output = json_encode($resp);
  }
  if ($code !== 200) http_response_code($code);
  // you'd have to be able to calculate the output size
  // on a 304 check
  //$filesize = strlen($output);
  echo $output;
  // why true? we don't have a false condition
  // and then we can't short return if desired for false
  return true;
}

// deprecate for sendJson
function sendRawResponse($mixed, $code = 200, $err = '') {
  if ($code !== 200) http_response_code($code);
  if (getQueryField('prettyPrint')) {
    // we need the HTML for the htmlspecialchars
    // and we need that to stop executing user generate js
    //header('Content-Type: text/html'); // should be the default
    echo '<pre>', htmlspecialchars(json_encode($mixed, JSON_PRETTY_PRINT)), "</pre>\n";
  } else {
    header('Content-Type: application/json');
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

//print_r($_SERVER);
//$headers = getallheaders();
//$txt = $req_method . ' ' . $req_path . ' ' . print_r($headers, 1);
//$txt = $req_method . ' ' . $req_path;
//file_put_contents('log.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

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
