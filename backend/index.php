<?php

// REST API

// if OPTIONS do CORS

// message queue

// read config
include 'config.php';

// 4chan GET board threads
// GET random board uri
// GET board list (pageinated)
// GET board thread list
// GET thread
// GET last X posts from thread
// POST board search
// POST thread search
// POST thread/reply

// how to handle authentication with frontend?

// connect to db
include 'lib/lib.model.php';
// FIXME: database type to select driver
$db_driver = 'mysql';
include 'lib/database_drivers/'.$db_driver.'.php';
$driver_name = $db_driver . '_driver';
$db = new $driver_name;

$db->connectDB(DB_HOST, DB_USER, DB_PWD, DB_NAME);

include 'lib/lib.board.php';

// build modules...
include 'lib/lib.modules.php';
enableModulesType('models');

function boardDBtoAPI(&$row) {
  global $db, $models;
  unset($row['boardid']);
  unset($row['json']);
  // decode user_id
}

function postDBtoAPI(&$row) {
  global $db, $models;
  $row['no'] = $row['postid'];
  unset($row['postid']);
  unset($row['json']);
  // decode user_id
}

function fourChanAPI($path) {
  global $db, $models;
  //https://a.4cdn.org/boards.json
  if (strpos($path, '/boards.json') !== false) {
    $res = $db->find($models['board']);
    $boards = array();
    while($row = mysqli_fetch_assoc($res)) {
      boardDBtoAPI($row);
      $boards[] = $row;
    }
    echo json_encode($boards);
  } else

  // https://a.4cdn.org/po/catalog.json
  if (strpos($path, '/catalog.json') !== false) {
    echo "board catalog<br>\n";
  } else

  // https://a.4cdn.org/po/threads.json
  if (strpos($path, '/threads.json') !== false) {
    echo "board threads<br>\n";
  } else

  //https://a.4cdn.org/archive.json
  if (strpos($path, '/archive.json') !== false) {
    echo "board threads<br>\n";
  } else

  // https://a.4cdn.org/po/thread/570368.json
  if (strpos($path, '/thread/') !== false) {
    $parts = explode('/', $path);
    $boardUri = $parts[2];
    $threadNum = str_replace('.json', '', $parts[4]);
    if (is_numeric($threadNum)) {
      $posts_model = getPostsModel($boardUri);
      $res = $db->find($posts_model, array('criteria'=>array(
        array('postid', '=', $threadNum),
      )));
      $posts = array();
      $row = mysqli_fetch_assoc($res);
      postDBtoAPI($row);
      $posts[] = $row;

      $res = $db->find($posts_model, array('criteria'=>array(
        array('threadid', '=', $threadNum),
      )));
      while($row = mysqli_fetch_assoc($res)) {
        postDBtoAPI($row);
        $posts[] = $row;
      }

      echo json_encode(array('posts'=>$posts));
      return;
    }
  } else

  // https://a.4cdn.org/po/2.json
  if (strpos($path, '.json') !== false) {
    $parts = explode('/', $path);
    if (isset($parts[3])) {
      $page = str_replace('.json', '', $parts[3]);
      if (is_numeric($page)) {
        $boardUri = $parts[2];
        // get threads for this page
        $posts_model = getPostsModel($boardUri);
        $res = $db->find($posts_model);
        $threads = array();
        while($row = mysqli_fetch_assoc($res)) {
          postDBtoAPI($row);
          $threads[] = $row;
        }
        echo json_encode($threads);
        return;
      }
    }
    // NON-standard 4chan api
    // board
    $board_uri = str_replace('.json', '', $parts[2]);
    $res = $db->find($models['board'], array('criteria'=>array(
      array('uri', '=', $board_uri),
    )));
    if (mysqli_num_rows($res)) {
      $row = mysqli_fetch_assoc($res);
      boardDBtoAPI($row);
      echo json_encode($row);
    } else {
      print_r($parts);
    }
  }
}

$response_template = array(
  'meta' => array(
    'code' => 200,
  ),
  'data' => array(
  ),
);

function getip() {
  $ip = empty($_SERVER['REMOTE_ADDR'])?'':$_SERVER['REMOTE_ADDR'];
  // cloudflare support
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  return $ip;
}

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

function hasPostVars($fields) {
  foreach($fields as $field) {
    if (empty($_POST[$field])) {
      sendResponse(array(), 400, 'Field "' . $field . '" required');
      return false;
    }
  }
  return true;
}

function loggedin() {
  global $db, $models;
  $sid = empty($_SERVER['HTTP_SID']) ? '' : $_SERVER['HTTP_SID'];
  $sesRes = $db->find($models['session'], array('criteria' => array(
    array('session', '=', $sid),
  )));
  // FIXME
  if (!mysqli_num_rows($sesRes)) {
    sendResponse(array(), 401, 'Invalid Session');
    return;
  }
  // FIXME
  $sesRow = mysqli_fetch_assoc($sesRes);
  if (time() > $sesRow['expires']) {
    sendResponse(array(), 401, 'Invalid Session');
    return;
  }
  return $sesRow['user_id'];
}

function lynxChanAPI($path) {
  global $db, $models;
  //echo "path[$path]<br>\n";
  if (strpos($path, '/registerAccount') !== false) {
    if (!hasPostVars(array('login', 'password', 'email'))) {
      return;
    }
    $emRes = $db->find($models['user'], array('criteria' => array(
      array('email', '=', $_POST['email']),
    )));
    // FIXME
    if (mysqli_num_rows($emRes)) {
      echo "email already has account";
      return;
    }
    $res = $db->find($models['user'], array('criteria' => array(
      array('username', '=', $_POST['login']),
    )));
    if (mysqli_num_rows($res)) {
      echo "already taken";
      return;
    }
    echo "Creating<br>\n";
    $id = $db->insert($models['user'], array(array(
      'username' => $_POST['login'],
      'email'    => $_POST['email'],
      'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
    )));
    $data = array('id'=>$id);
    sendResponse($data);
  } else
  if (strpos($path, '/login') !== false) {
    // login, password, remember
    if (!hasPostVars(array('login', 'password'))) {
      return;
    }
    $res = $db->find($models['user'], array('criteria' => array(
      array('username', '=', $_POST['login']),
    )));
    if (!mysqli_num_rows($res)) {
      return sendResponse(array(), 401, 'Incorrect login');
    }
    // FIXME:
    $row = mysqli_fetch_assoc($res);
    // password check
    if (!password_verify($_POST['password'], $row['password'])) {
      return sendResponse(array(), 401, 'Incorrect login');
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
  } else
  if (strpos($path, '/createBoard') !== false) {
    // boardUri, boardName, boardDescription, session
    $user_id = loggedin();
    if (!$user_id) {
      return;
    }
    if (!hasPostVars(array('boardUri', 'boardName', 'boardDescription'))) {
      return;
    }
    // FIXME check unique fields...
    $db->insert($models['board'], array(array(
      'uri'         => $_POST['boardUri'],
      'title'       => $_POST['boardName'],
      'description' => $_POST['boardDescription'],
      'owner_id'    => $user_id,
    )));
    $data = 'ok';
    sendResponse($data);
  } else
  if (strpos($path, '/newThread') !== false) {
    if (!hasPostVars(array('boardUri'))) {
      return;
    }
    $user_id = loggedin();
    if (!$user_id) {
      return;
    }
    $boardUri = $_POST['boardUri'];
    $posts_model = getPostsModel($boardUri);
    $id = $db->insert($posts_model, array(array(
      // noFlag, email, password, captcha, spoiler, flag
      'threadid' => 0,
      'resto' => 0,
      'name' => $_POST['name'],
      'sub'  => $_POST['subject'],
      'com'  => $_POST['message'],
      'sticky' => 0,
      'closed' => 0,
      'trip' => '',
      'capcode' => '',
      'country' => '',
    )));
    $data = $id;
    sendResponse($data);
  } else {
    sendResponse(array(), 404, 'Unknown route');
  }
}

if (strpos($_SERVER['PATH_INFO'], '/4chan/') !== false) {
  fourChanAPI($_SERVER['PATH_INFO']);
}
if (strpos($_SERVER['PATH_INFO'], '/lynx/') !== false) {
  lynxChanAPI($_SERVER['PATH_INFO']);
}

?>
