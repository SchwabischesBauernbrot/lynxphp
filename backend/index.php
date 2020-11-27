<?php

// REST API

// if OPTIONS do CORS

// message queue

// read config
include 'config.php';

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

$pipelines['api_4chan'] = new pipeline_registry;
$pipelines['api_lynx'] = new pipeline_registry;
$pipelines['api_opt'] = new pipeline_registry;

// transformations (x => y)
// access list (remove this, add this)
// change input, output
// change processing is a little more sticky...

function boardDBtoAPI(&$row) {
  global $db, $models;
  unset($row['boardid']);
  unset($row['json']);
  // decode user_id
  /*
  $res = $db->find($models['user'], array('criteria'=>array(
    array('userid', '=', $row['userid']),
  )));
  $urow = $db->get_row($res)
  $row['user'] = $urpw['username'];
  */
  unset($row['userid']);
}

function postDBtoAPI(&$row, $post_files_model) {
  global $db, $models;
  $files = array();
  $res = $db->find($post_files_model, array('criteria'=>array(
    array('postid', '=', $row['postid']),
  )));
  while($frow = $db->get_row($res)) {
    $files[] = $frow;
  }
  $row['no'] = $row['postid'];
  $row['files'] = $files;
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
    while($row = $db->get_row($res)) {
      boardDBtoAPI($row);
      $boards[] = $row;
    }
    echo json_encode($boards);
  } else
  // https://a.4cdn.org/po/catalog.json
  if (strpos($path, '/catalog.json') !== false) {
    global $tpp;
    $parts = explode('/', $path);
    $boardUri = $parts[2];
    $board = getBoardByUri($boardUri);
    if (!$board) {
      sendResponse(array(), 404, 'Board not found');
    }
    // pages, threads
    // get a list of threads
    $posts_model = getPostsModel($boardUri);
    $post_files_model = getPostFilesModel($boardUri);
    $res = $db->find($posts_model, array('criteria'=>array(
      array('threadid', '=', 0),
    ), 'order'=>'updated_at desc'));
    $page = 1;
    // FIXME: rewrite to be more memory efficient
    while($row = $db->get_row($res)) {
      postDBtoAPI($row, $post_files_model);
      $threads[$page][] = $row;
      if (count($threads[$page]) === $tpp) {
        $page++;
        $threads[$page] = array();
      }
    }
    $pages = $page; if ($pages === 1) $pages = 2;
    $res = array();
    for($i = 1; $i < $pages; $i++) {
      $res[] = array(
        'page' => $i,
        'threads' => $threads[$i],
      );
    }
    echo json_encode($res);
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
      $post_files_model = getPostFilesModel($boardUri);
      /*(
      $posts_model['children'] = array(
        array(
          'type' => 'left',
          'model' => $post_files_model,
        )
      );
      */

      $posts = array();
      $res = $db->find($posts_model, array('criteria'=>array(
        array('postid', '=', $threadNum),
      )));
      $row = $db->get_row($res);
      postDBtoAPI($row, $post_files_model);
      $posts[] = $row;

      $res = $db->find($posts_model, array('criteria'=>array(
        array('threadid', '=', $threadNum),
      ), 'order' => 'created_at'));
      while($row = $db->get_row($res)) {
        postDBtoAPI($row, $post_files_model);
        $posts[] = $row;
      }

      echo json_encode(array('posts'=>$posts));
      return;
    }
  } else

  // https://a.4cdn.org/po/2.json
  if (strpos($path, '.json') !== false) {
    $parts = explode('/', $path);
    //echo "paths count[", count($parts), "] [", print_r($parts, 1), "]<br>\n";
    if (count($parts) === 4) {
      $page = str_replace('.json', '', $parts[3]);
      if (is_numeric($page)) {
        global $tpp;
        $lastXreplies = 10;
        $boardUri = $parts[2];
        // get threads for this page
        $posts_model = getPostsModel($boardUri);
        if ($posts_model === false) {
          // this board does not exist
          sendResponse(array(), 404, 'Board not found');
          return;
        }
        $post_files_model = getPostFilesModel($boardUri);
        $limitPage = $page - 1;
        $res = $db->find($posts_model, array('criteria'=>array(
            array('threadid', '=', 0),
          ),
          'order'=>'updated_at desc',
          'limit' => $tpp . ($limitPage ? ',' . $limitPage : '')
        ));
        $threads = array();
        while($row = $db->get_row($res)) {
          $posts = array();
          // add thread
          postDBtoAPI($row, $post_files_model);
          $posts[] = $row;
          // add remaining posts
          $postRes = $db->find($posts_model, array('criteria'=>array(
            array('threadid', '=', $row['no']),
          ), 'order'=>'created_at desc', 'limit' => $lastXreplies));
          $resort = array();
          while($prow = $db->get_row($postRes)) {
            postDBtoAPI($prow, $post_files_model);
            $resort[] = $prow;
          }
          $posts = array_merge($posts, array_reverse($resort));
          $threads[] = array('posts' => $posts);
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
    if ($db->num_rows($res)) {
      $row = $db->get_row($res);
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
function getOptionalPostField($field) {
  return empty($_POST[$field])    ? '' : $_POST[$field];
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
    if ($db->num_rows($emRes)) {
      return sendResponse(array(), 403, 'Already has account');
      return;
    }
    $res = $db->find($models['user'], array('criteria' => array(
      array('username', '=', $_POST['login']),
    )));
    if ($db->num_rows($res)) {
      return sendResponse(array(), 403, 'Already Taken');
      return;
    }
    //echo "Creating<br>\n";
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
    if (!$db->num_rows($res)) {
      return sendResponse(array(), 401, 'Incorrect login - no username');
    }
    $row = $db->get_row($res);
    // password check
    if (!password_verify($_POST['password'], $row['password'])) {
      return sendResponse(array(), 401, 'Incorrect login - bad pass');
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
    $user_id = loggedIn();
    if (!$user_id) {
      return;
    }
    if (!hasPostVars(array('boardUri', 'boardName', 'boardDescription'))) {
      return;
    }
    $boardUri = strtolower($_POST['boardUri']);
    $res = $db->find($models['board'], array('criteria'=>array(
      array('uri', '=', $boardUri),
    )));
    if ($db->num_rows($res)) {
      return sendResponse(array(), 403, 'Board already exists');
    }

    // FIXME check unique fields...
    $db->insert($models['board'], array(array(
      'uri'         => $boardUri,
      'title'       => $_POST['boardName'],
      'description' => $_POST['boardDescription'],
      'owner_id'    => $user_id,
    )));
    $data = 'ok';
    sendResponse($data);
  } else
  if (strpos($path, '/files') !== false) {
    $hash = hash_file('sha256', $_FILES['files']['tmp_name']);
    move_uploaded_file($_FILES['files']['tmp_name'], 'storage/tmp/'.$hash);
    $data=array(
      'type' => $_FILES['files']['type'],
      'name' => $_FILES['files']['name'],
      'size' => $_FILES['files']['size'],
      'hash' => $hash,
    );
    sendResponse($data);
  } else
  if (strpos($path, '/newThread') !== false) {
    // require image with each thread
    if (!hasPostVars(array('boardUri', 'files'))) {
      return;
    }
    $user_id = (int)getUserID();
    $boardUri = $_POST['boardUri'];
    $posts_model = getPostsModel($boardUri);
    $id = $db->insert($posts_model, array(array(
      // noFlag, email, password, captcha, spoiler, flag
      'threadid' => 0,
      'resto' => 0,
      'name' => getOptionalPostField('name'),
      'sub'  => getOptionalPostField('subject'),
      'com'  => getOptionalPostField('message'),
      'sticky' => 0,
      'closed' => 0,
      'trip' => '',
      'capcode' => '',
      'country' => '',
    )));
    processFiles($boardUri, $_POST['files'], $id, $id);
    $data = $id;
    sendResponse($data);
  } else
  if (strpos($path, '/replyThread') !== false) {
    if (!hasPostVars(array('boardUri', 'threadId'))) {
      return;
    }
    $user_id = (int)getUserID();
    $boardUri = $_POST['boardUri'];
    $posts_model = getPostsModel($boardUri);
    $threadid = (int)$_POST['threadId'];
    // make sure threadId exists...
    $id = $db->insert($posts_model, array(array(
      // noFlag, email, password, captcha, spoiler, flag
      'threadid' => $threadid,
      'resto' => 0,
      'name' => getOptionalPostField('name'),
      'sub'  => getOptionalPostField('subject'),
      'com'  => getOptionalPostField('message'),
      'sticky' => 0,
      'closed' => 0,
      'trip' => '',
      'capcode' => '',
      'country' => '',
    )));
    $data = $id;
    // bump thread
    $urow = array('updated_at' => '');
    $db->update($posts_model, $urow, array('criteria'=>array(
      array('postid', '=', $threadid),
    )));
    processFiles($boardUri, $_POST['files'], $threadid, $id);
    sendResponse($data);
  } else
  if (strpos($path, '/account') !== false) {
    $user_id = loggedIn();
    if (!$user_id) {
      return;
    }
    $userRes = $db->findById($models['user'], $user_id);

    $res = $db->find($models['board'], array('criteria'=>array(
      array('owner_id', '=', $user_id),
    )));
    $ownedBoards = array();
    while($row = $db->get_row($res)) {
      boardDBtoAPI($row);
      $ownedBoards[] = $row;
    }
    echo json_encode(array(
      'noCaptchaBan' => false,
      'login' => $userRes['username'],
      'email' => $userRes['email'],
      'globalRole' => 99,
      //'disabledLatestPostings'
      //'volunteeredBoards'
      'boardCreationAllowed' => true,
      'ownedBoards' => $ownedBoards,
      //'settings'
      'reportFilter' => array(), // category filters for e-mail notifications
    ));
  } else {
    global $pipelines;
    $pipelines['api_lynx']->execute($path);
    sendResponse(array(), 404, 'Unknown route');
  }
}

function optAPI($path) {
  // is session still valid
  if (strpos($path, '/session') !== false) {
    $user_id = loggedIn();
    if (!$user_id) {
      return;
    }
    sendResponse(array('session' => 'ok'));
  } else
  if (strpos($path, '/boards/') !== false) {
    //
  } else
  if (strpos($path, '/myBoards') !== false) {
    $user_id = loggedIn();
    if (!$user_id) {
      return;
    }
    $res = $db->find($models['board']);
    $boards = array();
    while($row = $db->get_row($res)) {
      boardDBtoAPI($row);
      $boards[] = $row;
    }
    echo json_encode($boards);
  }
}

$path = empty($_SERVER['PATH_INFO']) ? '' : $_SERVER['PATH_INFO'];
if (strpos($path, '/4chan/') !== false) {
  fourChanAPI($path);
} else
if (strpos($path, '/lynx/') !== false) {
  lynxChanAPI($path);
} else
if (strpos($path, '/opt/') !== false) {
  optAPI($path);
}

?>
