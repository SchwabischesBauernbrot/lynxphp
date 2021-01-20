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
  $boards = listBoards();
  $res = array();
  foreach($boards as $b) {
    // FIXME: N+1s...
    // include posts, threads, last_activity
    $b['threads'] = getBoardThreadCount($b['uri']);
    $b['posts'] = getBoardPostCount($b['uri']);

    if ($b['threads']) {
      $posts_model = getPostsModel($b['uri']);
      $newestThreadRes = $db->find($posts_model, array('criteria'=>array(
          array('threadid', '=', 0),
      ), 'limit' => '1', 'order'=>'updated_at desc'));
      $newestThread = $db->toArray($newestThreadRes);
      $b['last'] = $newestThread[0];
    }
    $res[] = $b;
  }
  sendResponse($res);
});


$router->get('/myBoards', function($request) {
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  $boards = userBoards($user_id);
  sendResponse($boards);
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
