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
    $boards[$parts[0]] = true;
  }
}

$hasDeleteAccess = array();
foreach($boards as $uri => $t) {
  $hasDeleteAccess[$uri] = isBO($uri);
}

$removedThreads = 0;
$removedPosts   = 0;

$issues = array();

switch($action) {
  case 'delete':
    $password = getOptionalPostField('password');
    global $db;
    foreach($posts as $r) {
      $posts_model = getPostsModel($r['board']);
      $post = $db->findById($posts_model, $r['postid']);
      if (!$post) {
        //echo "No post [", $r['postid'], "]<br>\n";
        // is this key enough?
        $issues[$r['board'].'_'.$r['postid']] = 'post not found';
        continue;
      }
      if ($hasDeleteAccess[$r['board']] || ($post['password'] && $post['password'] === $password)) {
        // try to delete it
        if (!deletePost($r['board'], $r['postid'], false, $post)) {
          // FIXME: log error?
          $issues[$r['board'].'_'.$r['postid']] = 'deletion failed';
          continue;
        }
        if ($post['threadid']) {
          $removedPosts++;
        } else {
          $removedThreads++;
        }
      } else {
        $issues[$r['board'].'_'.$r['postid']] = 'access denied';
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
      if (getOptionalPostField('reasonReport')) $report['reason'] = getOptionalPostField('reasonReport');
      // make sure reason is unique?
      $data['json']['reports'][] = $report;
      updateBoard($r['board'], $data);
    }
    // FIXME: global report
    if (getOptionalPostField('globalReport')) {
      // also make a global report
    }
  break;
  default:
    // FIXME:
  break;
}

sendResponse(array(
  'removedThreads' => $removedThreads,
  'removedPosts' => $removedPosts,
  'request' => $posts,
  'hasDeleteAccess' => $hasDeleteAccess,
  'issues' => $issues,
));
?>
