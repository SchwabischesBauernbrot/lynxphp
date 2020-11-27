<?php

// load frontend config
include 'config.php';

// set up backend url, cache

// if OPTIONS

// if POST

// dispatch form data through post processing pipeline
// well initially I think we'll just have the form post to the backend directly

// if GET
// determine page from PHP_INFO

// interpolate boards list with template
// interpolate board threads list with index template
// interpolate board threads list with catalog template

// interpolate board search list with board list template
// interpolate thread search list with template

// serve site

include '../common/router.php';
$router = new Router;

// frontend libraries
include 'lib/lib.cache.php';
include 'lib/lib.http.php';
include 'lib/lib.handler.php';
include 'lib/lib.backend.php';

// frontend handlers
include 'handlers/homepage.php';
include 'handlers/login.php';
include 'handlers/signup.php';
include 'handlers/control_panel.php';
include 'handlers/boards.php';

if (!defined('BASE_HREF')) {
  define('BASE_HREF', dirname($_SERVER['SCRIPT_NAME']) . '/');
}

function getip() {
  $ip = empty($_SERVER['REMOTE_ADDR'])?'':$_SERVER['REMOTE_ADDR'];
  // cloudflare support
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  return $ip;
}

// should a handler set a variables (data structure)
// or define a set of functions
// functions can be variables...

function hasPostVars($fields) {
  foreach($fields as $field) {
    if (empty($_POST[$field])) {
      wrapContent('Field "' . $field . '" required');
      return false;
    }
  }
  return true;
}


$router->get('', function() {
  homepage();
});
$router->get('/boards.php', function() {
  getBoardsHandler();
});
$router->get('/overboard.php', function() {
  getOverboardHandler();
});

$router->get('/:uri/', function($params) {
  $boardUri = $params['uri'];
  getBoardPageHandler($boardUri, 1);
});
$router->get('/:uri/catalog', function($params) {
  $boardUri = $params['uri'];
  getBoardCatalogHandler($boardUri);
});
$router->get('/:uri/settings', function($params) {
  $boardUri = $params['uri'];
  getBoardSettingsHandler($boardUri);
});
$router->get('/:uri/banners', function($params) {
  $boardUri = $params['uri'];
  getBoardBannerHandler($boardUri);
});

$router->get('/:uri/thread/:num', function($params) {
  $boardUri = $params['uri'];
  $threadNum = str_replace('.html', '', $params['num']);
  getThreadHandler($boardUri, $threadNum);
});


$router->post('/:uri/post', function($params) {
  $boardUri = $params['uri'];
  // valid board name
  // validate results
  $files = array();
  if (isset($_FILES)) {
    if (is_array($_FILES['file']['tmp_name'])) {
      echo "detected multiple files<br>\n";
      foreach($_FILES['file']['tmp_name'] as $i=>$path) {
        $res = sendFile($path, $_FILES['file']['type'][$i], $_FILES['file']['name'][$i]);
        // check for error?
        $files[] = $res;
      }
    } else {
      $res = sendFile($_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['name']);
      // check for error?
      $files[] = $res;
    }
  }
  //print_r($files);
  // make post...
  if (empty($_POST['thread'])) {
    // new thead
    //echo "boardUri[$boardUri]<br>\n";
    $json = curlHelper(BACKEND_BASE_URL . 'lynx/newThread', array(
      // noFlag
      'email'    => $_POST['email'],
      'message'  => $_POST['message'],
      'subject'  => $_POST['subject'],
      'boardUri' => $boardUri,
      'password' => $_POST['postpassword'],
      // captcha
      'spoiler'  => empty($_POST['spoiler_all']) ? '' : $_POST['spoiler_all'],
      'files'    => json_encode($files),
      // flag
    ), array('HTTP_X_FORWARDED_FOR' => getip(), 'sid' => $_COOKIE['session']));
    echo "json[$json]<Br>\n";
    $result = json_decode($json, true);
    if (is_numeric($result['data'])) {
      // success
      redirectTo(BASE_HREF . $boardUri . '/');
    } else {
      wrapContent('Post Error: ' . print_r($result, 1));
    }
  } else {
    // reply
    //echo "boardUri[$boardUri]<br>\n";
    $json = curlHelper(BACKEND_BASE_URL . 'lynx/replyThread', array(
      // noFlag
      'threadId' => $_POST['thread'],
      'email'    => $_POST['email'],
      'message'  => $_POST['message'],
      'subject'  => $_POST['subject'],
      'boardUri' => $boardUri,
      'password' => $_POST['postpassword'],
      // captcha
      'spoiler'  => empty($_POST['spoiler_all']) ? '' : $_POST['spoiler_all'],
      // flag
      'files'    => json_encode($files),
    ), array('HTTP_X_FORWARDED_FOR' => getip(), 'sid' => $_COOKIE['session']));
    echo "json[$json]<Br>\n";
    $result = json_decode($json, true);
    if (is_numeric($result['data'])) {
      // success
      redirectTo(BASE_HREF . $boardUri . '/thread/' . $_POST['thread']);
    } else {
      wrapContent('Post Error: ' . print_r($result, 1));
    }
  }
});



$router->get('/signup.php', function() {
  getSignup();
});
$router->post('/signup.php', function() {
  postSignup();
});
$router->get('/login.php', function() {
  getLogin();
});
$router->post('/login.php', function() {
  postLogin();
});
$router->get('/control_panel.php', function() {
  getControlPanel();
});
$router->get('/logout.php', function() {
  getLogout();
});
$router->get('/create_board.php', function() {
  getCreateBoard();
});
$router->post('/create_board.php', function() {
  postCreateBoard();
});

// needs to go last...
$router->get('/:uri', function($params) {
  $boardUri = $params['uri'];
  // maybe only redir if the board exists...
  redirectTo(BASE_HREF . $boardUri . '/');
});

$router->exec(empty($_SERVER['REQUEST_METHOD']) ? 'GET' : $_SERVER['REQUEST_METHOD'], empty($_SERVER['PATH_INFO']) ? '' : $_SERVER['PATH_INFO']);

?>
