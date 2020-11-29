<?php

//
// Optimized routes for lynxphp
//

$router = new router;

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
  $threadCount = getThreadCount($boardUri);
  $threads = boardPage($boardUri, $pageNum);
  sendResponse(array(
    'board' => $boardData,
    'page1' => $threads,
    'threadsPerPage'   => $tpp,
    'threadCount' => $threadCount,
    'pageCount' => ceil($threadCount/$tpp),
  ));
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
  global $db, $models;
  $boardUri = str_replace('.json', '', $request['params']['board']);
  $boardData = getBoard($boardUri);
  if (!$boardData) {
    echo '[]';
    return;
  }
  echo json_encode($boardData);
});

return $router;

?>