<?php

$params = $get();

$boards = listBoards(array('publicOnly' => true));

// we need to sort this...
global $db;
$res = array();
foreach($boards as $b) {
  $posts_model = getPostsModel($b['uri']);
  $b['threads'] = getBoardThreadCount($b['uri'], $posts_model); // 1 query
  // if we bump the updated_at on boards we wouldn't need to do this query...
  if ($b['threads']) {
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
$logo = $settings['site']['logo'];
//echo "logo[$logo]<br>\n";
$size = getimagesize($settings['site']['logo']);

$settings['site']['logo'] = array(
  'url' => $logo,
  'w' => $size[0],
  'h' => $size[1],
  //'alt' => '',
);

// recent posts/images?
// are we trying to be lynxchan compatible?
sendResponse(array(
  'boards' => $res,
  'settings' => $settings,
));

?>
