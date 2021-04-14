<?php

// REST API

include '../common/post_vars.php';

// read backend config
include 'config.php';

// if OPTIONS do CORS

// message queue

$router = include '../common/router.php';

// one syscall to get the current time
$now = microtime(true);

// connect to db
include 'lib/database_drivers/' . DB_DRIVER . '.php';
$driver_name = DB_DRIVER . '_driver';
$db = new $driver_name;

// make a queue
// don't auto-detect, just get configuration
// FIXME: make configurable
include '../common/queue_implementations/db.php';
$queue_type_class = 'db' . '_queue_driver';
$queue = new $queue_type_class;

// set up workqueue
include '../common/workqueue.php';
$workqueue = new work_queue;

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

$routers = array();
$routers['4chan'] = include 'routes/4chan.php';
$routers['lynx']  = include 'routes/lynxchan_minimal.php';
$routers['opt']   = include 'routes/opt.php';

include 'lib/lib.board.php';
include 'lib/middlewares.php';
include 'interfaces/boards.php';
include 'interfaces/posts.php';
include 'interfaces/users.php';
include 'interfaces/files.php';
include 'interfaces/sessions.php';
include 'interfaces/settings.php';

$packages = array();
$packages['base'] = registerPackage('base');
registerPackageGroup('board');
registerPackageGroup('post');
registerPackageGroup('user');
registerPackageGroup('site');
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

function checkHeaders($mtime, $filesize = false) {
  // cached and non-cached need this
  $headers=array();
  if (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
  }
  // we don't always know the size
  $etag = false;
  if ($filesize) { // don't generate if not needed
    $etag = dechex($mtime) . '-' . dechex($filesize);
  }
  if ((!isset($headers['Cache-Control']) || (isset($headers['Cache-Control']) && $headers['Cache-Control'] !== 'no-cache'))
      && (
        ($etag && isset($headers['If-None-Match']) && $headers['If-None-Match']
           && strpos($headers['If-None-Match'], $etag)!==false) ||
        (isset($headers['If-Modified-Since']) && $headers['If-Modified-Since']
           && $lastmod == $headers['If-Modified-Since']))
      ) {
    header('HTTP/1.1 304 Not Modified');
    // why is this empty?
    header('Content-Type: ');
    // maybe just exit?
    return true;
  }
  // maybe return etag so it doesn't have to be generated?
  return false;
}

function sendResponse2($data, $options = array()) {
  global $response_template, $now;
  $code  = 200;
  $err   = '';
  $mtime = $now;
  $meta = array();
  if (isset($options['err']))     $err   = $options['err'];
  if (isset($options['code']))    $code  = $options['code'];
  if (isset($options['lastMod'])) $mtime = $options['lastMod'];
  if (isset($options['meta']))    $meta  = $options['meta'];

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
  $filesize = strlen($output);

  if ($mtime === $now) {
    // dont cache
    header ('Expires: '.gmdate('D M d H:i:s Y', 1)); // old
    header ('Proxy-Connection: keep-alive');
    header ('Cache-Control: no-store, must-revalidate, post-check=1, pre-check=2');
  } else {
    // take control over caching

    $httpauth = isset($_SERVER['PHP_AUTH_USER']);
    $ssl = false;
    if (isset($_SERVER['HTTPS'])) {
      $ssl = $_SERVER['HTTPS'] === 'on';
    }
    $public='';
    if ($httpauth || $ssl) {
      // public says to cache through SSL & httpauth
      // otherwise just cached in memory only
      $public = 'public, ';
    }
    $etag = false;
    if ($filesize) { // don't generate if not needed
      $etag = dechex($mtime) . '-' . dechex($filesize);
    }
    $lastmod = gmdate('D, d M Y H:i:s', $mtime).' GMT';
    header('Last-Modified: ' . $lastmod);
    if ($filesize) {
      header('Content-Length: ' . $filesize);
    }
    header('Cache-Control: ' . $public . 'must-revalidate');
    header('Vary: Accept-Encoding');
    if ($etag) {
      header('ETag: "' . $etag . '"');
    }
  }
  echo $output;
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
  if (getQueryField('prettyPrint')) {
    echo '<pre>', json_encode($resp, JSON_PRETTY_PRINT), "</pre>\n";
  } else {
    echo json_encode($resp);
  }
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