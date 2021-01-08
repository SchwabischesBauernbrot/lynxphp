<?php

$router = new router;

// https://a.4cdn.org/boards.json
$router->get('/boards.json', function($request) {
  $boards = listBoards();
  echo json_encode($boards);
});

// https://a.4cdn.org/po/catalog.json
$router->get('/:board/catalog.json', function($request) {
  global $tpp;
  $boardUri = $request['params']['board'];
  $page = boardCatalog($boardUri);
  if (!is_array($page)) {
    sendResponse(array(), 404, 'Board not found');
    return;
  }
  $pages = count($page);
  $res = array();
  for($i = 1; $i <= $pages; $i++) {
    $res[] = array(
      'page' => $i,
      'threads' => $page[$i],
    );
  }
  echo json_encode($res);
});

// FIXME: https://a.4cdn.org/po/threads.json
// FIXME: https://a.4cdn.org/archive.json

// https://a.4cdn.org/po/thread/570368.json
$router->get('/:board/thread/:thread', function($request) {
  $boardUri = $request['params']['board'];
  $threadNum = (int)str_replace('.json', '', $request['params']['thread']);
  $posts = getThread($boardUri, $threadNum);
  echo json_encode(array('posts'=>$posts));
});

// https://a.4cdn.org/po/2.json
$router->get('/:board/:page', function($request) {
  $boardUri = $request['params']['board'];
  $page = str_replace('.json', '', $request['params']['page']);
  $threads = boardPage($boardUri, $page);
  echo json_encode($threads);
});

return $router;

?>
