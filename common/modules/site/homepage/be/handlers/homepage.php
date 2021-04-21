<?php

$params = $get();

$boards = listBoards();

// we need to sort this...
global $db;
$res = array();
foreach($boards as $b) {
  $b['threads'] = getBoardThreadCount($b['uri']); // 1 query
  if ($b['threads']) {
    $posts_model = getPostsModel($b['uri']);
    $newestThreadRes = $db->find($posts_model, array('criteria'=>array(
      array('threadid', '=', 0), // 1 query
    ), 'limit' => '1', 'order'=>'updated_at desc'));
    $newestThread = $db->toArray($newestThreadRes);
    $db->free($newestThreadRes);
    $b['last'] = $newestThread[0];
  } else {
    $b['last'] = array('updated_at' => 0);
  }
  // sort by most recent
  $res[$b['last']['updated_at']] = $b;
}
krsort($res);
// top 10
$res = array_slice($res, 0, 10);

// FIXME: not very cacheable like this...
$settings = getSettings();

// recent posts/images?
// are we trying to be lynxchan compatible?
sendResponse(array(
  'boards' => $res,
  'settings' => $settings,
));

?>
