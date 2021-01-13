<?php
$params = $get();

$action = getQueryField('action');

// board-threadnum-postnum is the name...
$posts = array();
$boards = array();
foreach($_POST as $k => $v) {
  $parts = explode('-', $k);
  // could also start with board passed in...
  if (count($parts) === 3) {
    // FIXME: validate post
    $posts[]=array(
      'board'    => $parts[0],
      'threadid' => $parts[1],
      'postid'   => $parts[2],
    );
    $baords[$parts[0]] = true;
  }
}

$hasDeleteAccess = array();
foreach($boards as $uri => $t) {
  $hasDeleteAccess[$uri] = isBO();
}

$hasDeleteAccess = false;

$removedThreads = 0;
$removedPosts   = 0;


switch($action) {
  case 'delete':
    $password = $_POST['password'];
    global $db;
    foreach($posts as $r) {
      $posts_model = getPostsModel($r['board']);
      $post = $db->findById($posts_model, $r['postid']);
      if (!$post) continue;
      if ($hasDeleteAccess || $post['password'] === $password) {
        // try to delete it
        if (!$db->deleteById($posts_model, $r['postid'])) {
          // FIXME: log error?
          continue;
        }
        if ($post['threadid']) {
          $removedPosts++;
        } else {
          $removedThreads++;
        }
      }
    }
    //
  break;
  case 'report':
    // so create reports for these posts...
    foreach($posts as $r) {
      // lock? group by board?
      $data = getBoardByUri($r['board']);
      $report = array(
        'id' => uniqid(), // prevent race issues
        'ip' => getip(),
        'userid' => getUserID(),
        'postid' => $r['postid'],
        'created_at' => time(),
        'status' => 'open',
      );
      if ($_POST['reasonReport']) $report['reason'] = $_POST['reasonReport'];
      // make sure reason is unique?
      $data['json']['reports'][] = $report;
      updateBoard($r['board'], $data);
    }
    // FIXME: global report
    if ($_REQUEST['globalReport']) {
      // also make a global report
    }
  break;
}

sendResponse(array(
  'removedThreads' => $removedThreads,
  'removedPosts' => $removedPosts,
));
?>