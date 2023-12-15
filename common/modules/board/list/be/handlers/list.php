<?php

// list/be

global $db;
// default is popularity (desc)
$search = empty($_GET['search']) ? '' : $_GET['search'];
$sort = empty($_GET['sort']) ? 'activity' : $_GET['sort'];
$showInactive = empty($_GET['showInactive']) ? false : true;

// updated_at isn't good enough, last
$sortByField = $sort === 'popularity' ? 'posts' : 'last_post';

$boards = listBoards(array(
  'search'     => $search,
  'sort'       => $sort,
  'publicOnly' => true,
  'showInactive' => $showInactive,
));
$res = array();
$noLast = array();
foreach($boards as $b) {
  // FIXME: N+1s... (yea page is almost at 1s for 40 boards)
  // include posts, threads, last_activity
  $posts_model = getPostsModel($b['uri']);
  if (!$posts_model) {
    return sendResponse2(array(), array(
      'code' => 500,
      'err'  => 'Board database integrity error ' . $b['uri'],
    ));
  }
  $b['threads'] = getBoardThreadCount($b['uri'], $posts_model); // 1 query
  $b['posts'] = getBoardPostCount($b['uri'], $posts_model); // 1 query

  // we can't use last_post because we're pulling the full thread here...
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
$direction = empty($_GET['direction']) ? 'desc' : $_GET['direction'];
if ($direction === 'desc') {
  ksort($res);
} else {
  krsort($res);
}
$res = array_merge($noLast, $res);
// FIXME: not very cacheable like this...
sendResponse2(array('settings' => getSettings(), 'boards' => array_values($res)));
