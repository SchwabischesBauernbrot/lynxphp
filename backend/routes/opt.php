<?php

//
// Optimized routes for lynxphp
//

$router = new router;

$router->get('/check', function($request) {
  // db check...
  global $db;
  sendResponse(array('check' => ($db->conn !== null) ? 'ok' : 'not ok'));
});

$router->get('/session', function($request) {
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  sendResponse(array('session' => 'ok'));
}, array(
  'contentType' => 'application/json',
  'databaseTables' => array('user_sessions'),
));

/*
function handleCFE($request) {
  global $db, $models;
  echo "Thank you for choosing PHPLynx<br>\n";
}

$router->get('/createFrontEnd', handleCFE); // official
$router->get('/createfrontend', handleCFE); // ux
*/

$router->get('/boards/:uri/:page', function($request) {
  global $tpp;

  $boardUri = $request['params']['uri'];
  $pageNum = $request['params']['page'] ? (int)$request['params']['page'] : 1;


  $boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
  if (!$boardData) {
    return sendResponse(array(), 404, 'Board does not exist');
  }
  $posts_model = getPostsModel($boardUri);

  $threadCount = getBoardThreadCount($boardUri, $posts_model);
  $threads = boardPage($boardUri, $posts_model, $pageNum);
  sendResponse(array(
    'board' => $boardData,
    'page1' => $threads,
    'threadsPerPage'   => $tpp,
    'threadCount' => $threadCount,
    'pageCount' => ceil($threadCount/$tpp),
  ));
}, array(
  'contentType' => 'application/json',
  'databaseTables' => array('user_sessions', 'board_{{uri}}_public_posts',
    'board_{{uri}}_public_post_files', 'boards'
  ),
));

// board data + thread data
// would be good to include the banners data too
// need a pipeline for that..
$router->get('/:board/thread/:thread', function($request) {
  global $tpp;
  $boardUri = $request['params']['board'];
  $boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
  if (!$boardData) {
    echo '[]';
    return;
  }
  $posts_model = getPostsModel($boardUri);
  $threadNum = (int)str_replace('.json', '', $request['params']['thread']);
  $boardData['threadCount'] = getBoardThreadCount($boardUri, $posts_model);
  $boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);
  $boardData['posts'] = getThread($boardUri, $threadNum, $posts_model);
  sendResponse($boardData);
});

// https://a.4cdn.org/po/catalog.json
$router->get('/:board/catalog.json', function($request) {
  global $tpp;
  $boardUri = $request['params']['board'];
  $page = boardCatalog($boardUri);
  if (!is_array($page)) {
    // boardCatalog handles this
    return;
  }
  $boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
  $pages = count($page);
  // FIXME: just return a list of threads...
  // also be able to page count?
  $res = array();
  for($i = 1; $i <= $pages; $i++) {
    $res[] = array(
      'page' => $i,
      'threads' => $page[$i],
    );
  }
  sendResponse(array(
    'pages' => $res,
    'board' => $boardData,
  ));
});

$router->get('/boards.json', function($request) {
  global $db;
  // default is popularity (desc)
  $search = empty($_GET['search']) ? '' : $_GET['search'];
  $sort = empty($_GET['sort']) ? 'activity' : $_GET['sort'];

  // updated_at isn't good enough, last
  $sortByField = $sort === 'popularity' ? 'posts' : 'last';

  $boards = listBoards(array(
    'search'     => $search,
    'sort'       => $sort,
    'publicOnly' => true,
  ));
  $res = array();
  $noLast = array();
  foreach($boards as $b) {
    // FIXME: N+1s... (yea page is almost at 1s for 40 boards)
    // include posts, threads, last_activity
    $posts_model = getPostsModel($b['uri']);
    if (!$posts_model) {
      return sendResponse(array(), 500, 'Board database integrity error ' . $b['uri']);
    }
    $b['threads'] = getBoardThreadCount($b['uri'], $posts_model); // 1 query
    $b['posts'] = getBoardPostCount($b['uri'], $posts_model); // 1 query

    if ($b['threads']) {
      //$posts_model = getPostsModel($b['uri']);
      $newestThreadRes = $db->find($posts_model, array('criteria'=>array(
          array('threadid', '=', 0),
      ), 'limit' => '1', 'order'=>'updated_at desc')); // 1 query
      $newestThread = $db->toArray($newestThreadRes);
      $db->free($newestThreadRes);
      $b['last'] = $newestThread[0];
    }
    if ($sortByField === 'last') {
      if (isset($b[$sortByField])) {
        $res[$b[$sortByField]['updated_at']] = $b;
      } else {
        $noLast[] = $b;
      }
    } else {
      $res[$b[$sortByField]] = $b;
    }
  }
  ksort($res);
  $res = array_merge($noLast, $res);
  // FIXME: not very cacheable like this...
  sendResponse(array('settings' => getSettings(), 'boards' => array_values($res)));
});


$router->get('/myBoards', function($request) {
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  $boards = userBoards($user_id);
  sendResponse($boards);
});

// does this user have this perms
// on optional object?
$router->get('/perms/:perm', function($request) {
  $user_id = loggedIn();
  if (!$user_id) {
    // well if you anon you don't get EXTRA permissions
    return; // already sends something...
  }
  $access = isUserPermitted($user_id, $request['params']['perm'], $request['target']);
  sendResponse(array(
    'access' => $access,
    'user_id' => $user_id,
  ));
});

function verifyChallengedSignatureHandler() {
  if (!hasPostVars(array('chal', 'sig'))) {
    // hasPostVars already outputs
    return;
  }
  $chal = $_POST['chal'];
  $sig  = $_POST['sig'];
  include '../common/sodium/autoload.php';
  // validate chal is one we issued? why?
  // so we can't reuse an old chal
  // well at least
  // FIXME: make sure it's not expired
  global $db, $models;
  $res = $db->find($models['auth_challenge'], array('criteria' =>
    array('challenge' => $chal)
  ));
  if (!$db->num_rows($res)) {
    $db->free($res);
    return sendResponse(array(), 401, 'challenge not found');
  }
  $row = $db->get_row($res);
  $db->free($res);
  // make sure no one can replay
  $db->deleteById($models['auth_challenge'], $row['challengeid']);
  $edPkBin = base64_decode($row['publickey']); // it's ed signing key in b64

  // prove payload was from user and not just a guessed challenge
  if (!\Sodium\crypto_sign_verify_detached($sig, $chal, $edPkBin)) {
    return sendResponse(array(), 401, 'signature verification failed');
  }
  return $edPkBin;
}

function loginResponseMaker($user_id, $upgradedAccount = false) {
  if (!$user_id) {
    return sendResponse(array(), 500, 'logging in as no user');
  }
  $sesrow = ensureSession($user_id);
  if (!isset($sesrow['created']) && $sesrow['userid'] != $user_id) {
    // there's already a session
    return sendResponse(array(), 400, 'You passed an active session');
  }
  // and return it
  $data = array(
    'session' => $sesrow['session'],
    'ttl'     => $sesrow['expires'],
    'upgradedAccount' => $upgradedAccount,
  );
  sendResponse($data);
}

// has to be last...
// non-standard 4chan api - lets disable for now
// /opt should have replaced this
$router->get('/:board', function($request) {
  global $db, $models, $tpp;
  $boardUri = str_replace('.json', '', $request['params']['board']);
  $boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
  if (!$boardData) {
    echo '[]';
    return;
  }
  $posts_model = getPostsModel($boardUri);
  $boardData['threadCount'] = getBoardThreadCount($boardUri, $posts_model);
  $boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);
  sendResponse($boardData);
});

return $router;

?>