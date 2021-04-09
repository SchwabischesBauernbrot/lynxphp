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
});

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

  $boardData = getBoard($boardUri);
  if (!$boardData) {
    return sendResponse(array(), 404, 'Board does not exist');
  }
  boardDBtoAPI($boardData);
  $threadCount = getBoardThreadCount($boardUri);
  $threads = boardPage($boardUri, $pageNum);
  sendResponse(array(
    'board' => $boardData,
    'page1' => $threads,
    'threadsPerPage'   => $tpp,
    'threadCount' => $threadCount,
    'pageCount' => ceil($threadCount/$tpp),
  ));
});

// board data + thread data
// would be good to include the banners data too
// need a pipeline for that..
$router->get('/:board/thread/:thread', function($request) {
  global $tpp;
  $boardUri = $request['params']['board'];
  $boardData = getBoard($boardUri);
  if (!$boardData) {
    echo '[]';
    return;
  }
  $threadNum = (int)str_replace('.json', '', $request['params']['thread']);
  $boardData['threadCount'] = getBoardThreadCount($boardUri);
  $boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);
  $boardData['posts'] = getThread($boardUri, $threadNum);
  sendResponse($boardData);
});

/*
$router->get('/boards/:uri/catalog', function($request) {
  global $tpp;
  $boardUri = $request['params']['board'];
  $threads = boardCatalog($boardUri);
  if (!$threads) {
    sendResponse(array(), 404, 'Board not found');
    return;
  }
  $pages = ceil(count($threads) / $tpp);
  $res = array();
  for($i = 1; $i <= $pages; $i++) {
    $res[] = array(
      'page' => $i,
      'threads' => $threads[$i],
    );
  }
  echo json_encode($res);
});
*/

$router->get('/boards.json', function($request) {
  global $db;
  $search = empty($_GET['search']) ? '' : $_GET['search'];
  $sort = empty($_GET['sort']) ? 'popularity' : $_GET['sort'];

  $sortByField = $sort === 'popularity' ? 'posts' : 'updated_at';
  //echo "sortByField[$sortByField]<br>\n";

  $boards = listBoards($sort, $search);
  $res = array();
  foreach($boards as $b) {
    // FIXME: N+1s... (yea page is almost at 1s for 40 boards)
    // include posts, threads, last_activity
    $b['threads'] = getBoardThreadCount($b['uri']); // 1 query
    $b['posts'] = getBoardPostCount($b['uri']); // 1 query

    if ($b['threads']) {
      $posts_model = getPostsModel($b['uri']);
      $newestThreadRes = $db->find($posts_model, array('criteria'=>array(
          array('threadid', '=', 0),
      ), 'limit' => '1', 'order'=>'updated_at desc')); // 1 query
      $newestThread = $db->toArray($newestThreadRes);
      $db->free($newestThreadRes);
      $b['last'] = $newestThread[0];
    }
    //echo "sortby[", print_r($b[$sortByField], 1), "]<br>\n";
    $res[$b[$sortByField]] = $b;
  }
  if ($sortByField === 'popularity') {
    krsort($res);
  } else {
    ksort($res);
  }
  sendResponse(array_values($res));
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

// non-standard 4chan api - lets disable for now
// /opt should have replaced this
$router->get('/:board', function($request) {
  global $db, $models, $tpp;
  $boardUri = str_replace('.json', '', $request['params']['board']);
  $boardData = getBoard($boardUri);
  if (!$boardData) {
    echo '[]';
    return;
  }
  $boardData['threadCount'] = getBoardThreadCount($boardUri);
  $boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);
  sendResponse($boardData);
});

return $router;

?>
