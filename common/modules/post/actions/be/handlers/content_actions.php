<?php
$params = $get();

$action = getQueryField('action');

$issues = array();
// board-threadnum-postnum is the name...
$posts = array();
$boards = array();
foreach($_POST as $k => $v) {
  $parts = explode('-', $k);
  // could also start with board passed in...
  if (count($parts) === 3) {
    // FIXME: validate post
    if ($parts[1] === 'ThreadNum') {
      // we need to look this up
      $posts_model = getPostsModel($parts[0]);
      if ($posts_model) {
        global $db;
        $row = $db->findById($posts_model, $parts[2]);
        if ($row) {
          // doesn't really matter if it's already deleted
          // maybe or reporting...

          if (!$row['threadid']) {
            // this post is a thread
            $parts[1] = $parts[2];
          } else {
            // reply
            $parts[1] = $row['threadid'];
          }
        }
      }
    }
    $posts[]=array(
      'board'    => $parts[0],
      'threadid' => $parts[1],
      'postid'   => $parts[2],
    );
    $boards[$parts[0]] = true;
  } else {
    // FIXME put in issues...
  }
}

$io = array('log' => array());

// are they logged in?
$user_id = getUserID();

$hasBoardPostDeletionAccess = array();
foreach($boards as $uri => $t) {
  // b means board permission check
  $hasBoardPostDeletionAccess[$uri] = isUserPermitted($user_id, 'delete_post', 'b/' . $uri);
}

$removedThreads = 0;
$removedPosts   = 0;
$added  = 0;
$data = array();

global $pipelines;

switch($action) {
  case 'delete':
    $password = getQueryField('password');
    global $db;
    foreach($posts as $r) {
      // FIXME: call once per r['board']
      //print_r($r);
      $uri = $r['board'];
      $posts_model = getPostsModel($uri);
      // no all _s are .s
      if (!$posts_model && strpos($uri, '_')!==false) {
        $board = str_replace('_', '.', $uri);
        //echo "test[$newUri]<br>\n";
        $posts_model = getPostsModel($uri);
      }
      //echo "test[", gettype($posts_model), "]<br>\n";
      if (!$posts_model) {
        //echo "No post [", $r['postid'], "]<br>\n";
        // is this key enough?
        $issues[$uri] = 'board not found';
        continue;
      }
      $post = $db->findById($posts_model, $r['postid']);
      if (!$post) {
        //echo "No post [", $r['postid'], "]<br>\n";
        // is this key enough?
        $issues[$uri.'_'.$r['postid']] = 'post not found';
        continue;
      }
      
      $allowDelete = false;
      if ($hasBoardPostDeletionAccess[$uri]) {
        $allowDelete = true;
      } else 
      if (isUserPermitted($user_id, 'delete_post', 'p/' . $uri . '/' . $r['postid'])) {
        $allowDelete = true;
      } else {
          // we need a pipeline here that handles password
          // well password is kind core... maybe not...
          $io = array(
            'uri' => $r['board'],
            'threadid' => $r['threadid'],
            'postid' => $r['postid'],
            'post' => $post,
            'posts_model' => $posts_model,
            'password' => $password,
            'allowDelete' => $allowDelete,
            'log' => array(),
          );
          $pipelines[PIPELINE_BE_CONTENTACTIONS_DELETE]->execute($io);
          $allowDelete = $io['allowDelete'];
        //}
      }

      if ($allowDelete) {
        // try to delete it
        if (!deletePost($uri, $r['postid'], array('post' => $post, 'posts_model' => $posts_model))) {
          // FIXME: log error?
          $issues[$uri . '_' . $r['postid']] = 'deletion failed';
          continue;
        }
        // what are these supposed to be according to spec?
        // form doesn't say output
        // and since not api, I'm not sure it matters
        // looks like counts though

        // is reply or thread?
        if ($post['threadid']) {
          $removedPosts++;
        } else {
          $removedThreads++;
        }
      } else {
        $issues[$uri . '_' . $r['postid']] = 'access denied';
      }
    }
    //
  break;
  case 'report':
    // so create reports for these posts...
    $userid = getUserID();
    $ip = getip();
    foreach($posts as $i => $r) {
      // lock? group by board?
      if (!$r['postid']) {
        $issues[$r['board'] . '_' . $i] = 'post not given';
      }
      $data = getBoardByUri($r['board']);

      // make sure we don't already have this post in an open report
      $add = true;
      foreach($data['json']['reports'] as $k=>$er) {
        // maybe we should see if we already closed a report
        // new report might have more info though
        if ($er['status'] !== 'open') continue;
        if ($r['postid'] === $er['postid']) {
          // add if reason is the same
          $reason = getOptionalPostField('reasonReport');
          // update
          $add = false;
          // if reporter is different, don't do anything...
          if ($er['userid'] === $userid) {
            if ($reason) {
              if ($reason === $er['reason']) continue;
            } else {
              // legacy fix?
              if (empty($er['erason'])) continue;
            }
          }
          if (!isset($data['json']['reports'][$k]['addition_reporter'])) {
            $data['json']['reports'][$k]['addition_reporter'] = array();
          }
          // already have id, postid, status
          $rec = array(
            'created_at' => time(),
            'ip' => $ip,
            'userid' => $userid,
          );
          if ($reason) $rec['reason'] = $reason;
          $data['json']['reports'][$k]['addition_reporter'][] = $rec;
        }
      }

      if ($add) {
        $report = array(
          'id' => uniqid(), // prevent race issues
          'ip' => $ip,
          'userid' => $userid,
          'postid' => $r['postid'],
          'created_at' => time(),
          'status' => 'open',
        );
        if (getOptionalPostField('reasonReport')) $report['reason'] = getOptionalPostField('reasonReport');

        $data['json']['reports'][] = $report;
        $added++;
        updateBoardJson($r['board'], $data['json']);
      } else {
        // we already have a report or someone already reported it or
        //
      }
    }
    // FIXME: global report
    // always make a global report too?
    //if (getOptionalPostField('globalReport')) {
      // also make a global report
    //}
  break;
  case 'ban':
    $removedPosts = 1;
  break;
  default:
    $issues[] = 'Unknown Action';
  break;
}

// probably should log stuff in the board log...

$didSomething = $removedThreads + $removedPosts + $added;

sendJson(array(
  'auth' => null,
  'status' => $didSomething ? 'ok' : 'error',
  'data' => $didSomething ? null : join("\n", $issues),
  // so ThreadNum could be passed in as "ThreadNum"
  // which means we need to return it somewhere...
  // tough because there maybe multiple
  'request' => $posts,
  'issues' => $issues,
  // the rest can be debug
  'debug'=> array(
    'added' => $added,
    'data' => $data,
    'action' => $action,
    'removedThreads' => $removedThreads,
    'removedPosts' => $removedPosts,
    'reportsAdded' => $added,
    'password' => $password,
    'saltpassword' => md5(BACKEND_KEY . $password),
    //'postpasswod' => $post['password'],
    'hasDeleteAccess' => $hasBoardPostDeletionAccess,
    'ioLog' => $io['log'],
    // you get this from dev tools...
    //'_POST' => $_POST,
  )
));

/*
sendResponse(array(
  'removedThreads' => $removedThreads,
  'removedPosts' => $removedPosts,
  'reportsAdded' => $added,
  'request' => $posts,
  'hasDeleteAccess' => $hasBoardPostDeletionAccess,
  'issues' => $issues,
));
*/

?>
