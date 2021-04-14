<?php

$router = new router;

/*
Do not make more than one request per second.
Thread updating should be set to a minimum of 10 seconds, preferably higher.
Use If-Modified-Since when doing your requests.
Make API requests using the same protocol as the app. Only use SSL when a user is accessing your app over HTTPS.
*/

// https://a.4cdn.org/boards.json
$router->get('/boards.json', function($request) {
  $boards = listBoards();
  if (getQueryField('prettyPrint')) {
    echo '<pre>', json_encode($boards, JSON_PRETTY_PRINT), "</pre>\n";
  } else {
    echo json_encode($boards);
  }
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
  $pages = count($page);
  $res = array();
  for($i = 1; $i <= $pages; $i++) {
    $res[] = array(
      'page' => $i,
      'threads' => $page[$i],
    );
  }
  if (getQueryField('prettyPrint')) {
    echo '<pre>', json_encode($res, JSON_PRETTY_PRINT), "</pre>\n";
  } else {
    echo json_encode($res);
  }
});

// Thread list
// https://a.4cdn.org/po/threads.json
$router->get('/:board/threads.json', function($request) {
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
    $threads = array();
    foreach($page[$i] as $t) {
      // no, last_modified, replies
      $thread = array(
        'no'            => $t['no'],
        //'replies'       => empty($t['reply_count']) ? null : $t['reply_count'],
        'replies'       => $t['reply_count'],
        'last_modified' => $t['updated_at']
      );
      $threads[] = $thread;
    }
    $res[] = array(
      'page' => $i,
      'threads' => $threads,
    );
  }
  if (getQueryField('prettyPrint')) {
    echo '<pre>', json_encode($res, JSON_PRETTY_PRINT), "</pre>\n";
  } else {
    echo json_encode($res);
  }
});

// FIXME: https://a.4cdn.org/archive.json

// Indexes
// https://a.4cdn.org/po/2.json
$router->get('/:board/:page', function($request) {
  $boardUri = $request['params']['board'];
  $page = str_replace('.json', '', $request['params']['page']);
  $threads = boardPage($boardUri, $page);
  $res = array(
    'threads' => $threads,
  );
  if (getQueryField('prettyPrint')) {
    echo '<pre>', json_encode($res, JSON_PRETTY_PRINT), "</pre>\n";
  } else {
    echo json_encode($res);
  }
});

// Thread endpoint
// https://a.4cdn.org/po/thread/570368.json
$router->get('/:board/thread/:thread', function($request) {
  $boardUri = $request['params']['board'];
  $threadNum = (int)str_replace('.json', '', $request['params']['thread']);
  $posts_model = getPostsModel($boardUri);
  if (!$posts_model) {
    echo '[]';
    return;
  }
  $posts = getThread($boardUri, $threadNum);
  // board doesn't not exist
  if (getQueryField('prettyPrint')) {
    echo '<pre>', json_encode($posts, JSON_PRETTY_PRINT), "</pre>\n";
  } else {
    echo json_encode($posts);
  }
});

return $router;

?>
