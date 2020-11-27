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

function handleRoute($path) {
  //print_r($_SERVER);
  switch($_SERVER['REQUEST_METHOD']) {
    case 'POST':
      echo "frontend POST $path<br>\n";
      if ($path === '/login.php') {
        postLogin();
      } else
      if ($path === '/signup.php') {
        postSignup();
      } else
      if ($path === '/create_board.php') {
        postCreateBoard();
      } else {
        // get board data
        $parts = explode('/', $path);
        $boardUri = $parts[1];
        //echo "boardUri[$boardUri]<br>\n";
        $page1 = getBoardPage($boardUri, 1);
        if (is_array($page1)) {
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
          return;
        }
        wrapContent('404');
      }
    break;
   default:
    case 'GET':
      if ($path === '') {
        homepage();
      } else
      if ($path === '/login.php') {
        getLogin();
      } else
      if ($path === '/signup.php') {
        getSignup();
      } else
      if ($path === '/control_panel.php') {
        getControlPanel();
      } else
      if ($path === '/create_board.php') {
        getCreateBoard();
      } else
      if ($path === '/boards.php') {
        getBoardsHandler();
      } else
      if ($path === '/overboard.php') {
        getOverboardHandler();
      } else {
        $dirs = substr_count($path, '/');
        //echo "dirs[$dirs]<br>\n";
        if ($dirs === 1) {
          $parts = explode('/', $path);
          $boardUri = $parts[1];
          // enforce board URIs be a path
          if ($path[strlen($path) - 1] !== '/') {
            // BASE_HREF ends in /
            // and path will start with /
            $adjPath = substr($path, 1);
            //echo "test[", BASE_HREF . $adjPath . '/', "]<br>\n";
            redirectTo(BASE_HREF . $adjPath . '/');
            return;
          }
        } else if ($dirs === 3) {
          $parts = explode('/', $path);
          $boardUri = $parts[1];
          if ($parts[2] === 'thread') {
            $threadNum = str_replace('.html', '', $parts[3]);
            if (is_numeric($threadNum)) {
              return getThreadHandler($boardUri, $threadNum);
            }
          }
        } else
        if ($dirs === 2) {
          // get board data
          $boardUri = trim($path, '/');
          // board existence check...
          $page1 = getBoardPage($boardUri, 1);
          if (is_array($page1)) {
            $parts = explode('/', $path);
            if ($parts[2] === 'catalog') {
              $boardUri = $parts[1];
              return getBoardCatalogHandler($boardUri);
            }
            return getBoardPageHandler($boardUri, 1, $page1);
          }
        }
        echo "frontend GET $path<br>\n";
      }
    break;
  }
}
handleRoute(empty($_SERVER['PATH_INFO']) ? '' : $_SERVER['PATH_INFO']);

?>
