<?php

function boardDBtoAPI(&$row) {
  global $db, $models;
  unset($row['boardid']);
  //if ($row['json']) $row['json'] = json_decode($row['json'], true);
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

// get list of boards
function listBoards() {
  global $db, $models;
  $res = $db->find($models['board']);
  $boards = array();
  while($row = $db->get_row($res)) {
    boardDBtoAPI($row);
    $boards[] = $row;
  }
  return $boards;
}
// get single board
function getBoard($boardUri) {
  global $db, $models;
  $res = $db->find($models['board'], array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));
  $row = $db->get_row($res);
  boardDBtoAPI($row);
  return $row;
}
// get board thread
// create board

function boardPage($boardUri, $page = 1) {
  global $db, $tpp;
  $page = (int)$page;
  $lastXreplies = 10;
  // get threads for this page
  $posts_model = getPostsModel($boardUri);
  if ($posts_model === false) {
    // this board does not exist
    sendResponse(array(), 404, 'Board not found');
    return;
  }
  $post_files_model = getPostFilesModel($boardUri);
  $limitPage = $page - 1; // make it start at 0
  //echo "page[$page] limitPage[$limitPage]<br>\n";
  $res = $db->find($posts_model, array('criteria'=>array(
      array('threadid', '=', 0),
    ),
    'order'=>'updated_at desc',
    'limit' => ($limitPage ? ($limitPage * $tpp) . ',' : '') . $tpp
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
  return $threads;
}

function boardCatalog($boardUri) {
  global $db, $tpp;
  $board = getBoardByUri($boardUri);
  if (!$board) {
    return false;
  }
  // pages, threads
  // get a list of threads
  $posts_model = getPostsModel($boardUri);
  $post_files_model = getPostFilesModel($boardUri);
  // get a list of threads sorted by bump
  $res = $db->find($posts_model, array('criteria' => array(
    array('threadid', '=', 0),
  ), 'order'=>'updated_at desc'));
  $page = 1;
  // FIXME: rewrite to be more memory efficient
  $threads = array();
  while($row = $db->get_row($res)) {
    postDBtoAPI($row, $post_files_model);
    $threads[$page][] = $row;
    if (count($threads[$page]) === $tpp) {
      $page++;
      $threads[$page] = array();
    }
  }
  //echo "page[$page]<br>\n";
  return $threads;
}

// optimization
function getThreadCount($boardUri) {
  global $db;
  $posts_model = getPostsModel($boardUri);
  $threadCount = $db->count($posts_model, array('criteria'=>array(
      array('threadid', '=', 0),
  )));
  return $threadCount;
}

?>
