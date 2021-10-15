<?php

global $db;
// default is popularity (desc)
$search = empty($_GET['search']) ? '' : $_GET['search'];
$sort = empty($_GET['sort']) ? 'activity' : $_GET['sort'];

// updated_at isn't good enough, last
$sortByField = $sort === 'popularity' ? 'posts' : 'last';

$boards = listBoards(array(
  'search'     => $search,
  'sort'       => $sort,
  'publicOnly' => true,
));
$res = array();
$noLast = array();
foreach($boards as $b) {
  // FIXME: N+1s... (yea page is almost at 1s for 40 boards)
  // include posts, threads, last_activity
  $posts_model = getPostsModel($b['uri']);
  if (!$posts_model) {
    return sendResponse(array(), 500, 'Board database integrity error ' . $b['uri']);
  }
  $b['threads'] = getBoardThreadCount($b['uri'], $posts_model); // 1 query
  $b['posts'] = getBoardPostCount($b['uri'], $posts_model); // 1 query

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
ksort($res);
$res = array_merge($noLast, $res);
// FIXME: not very cacheable like this...
sendResponse(array('settings' => getSettings(), 'boards' => array_values($res)));
