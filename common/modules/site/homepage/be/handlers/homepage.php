<?php
// be
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
      array('deleted', '=', 0), // 1 query
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
// why not? becuase of user settings
// we could always cache the core and inject user settings after the fact
// but I think we're talking 304 status, which means we can't pass user settings at all in this call
// which might be for the best
$settings = getSettings();
$logo = empty($settings['site']['logo']) ? '' : $settings['site']['logo'];
//echo "logo[$logo]<br>\n";
$size = $logo ? getimagesize($settings['site']['logo']) : array(0, 0);

$settings['site']['logo'] = array(
  'url' => $logo,
  'w' => $size[0],
  'h' => $size[1],
  //'alt' => '',
);

// recent posts/images
// overboard
global $db, $models;

// need to join board to get the settings
$ob_extended = $models['overboard_thread'];
$boardTable = modelToTableName($models['overboard_thread']);
if ($db->btTables) {
  $boardTable = '`' . $boardTable . '`';
}

/*
$ob_extended['children'] = array(
  array(
    'model' => $models['board'],
    'pluck' => array('ALIAS.json as board_json'),
    'on' => array(
      array('uri', '=', $db->make_direct($boardTable . '.uri')),
    ),
  ),
);
*/

$res2 = $db->find($ob_extended, array(
  'order' => 'updated_at desc',
  'limit' => 50, // FIXME: make adjustable
));
$newFiles = array();
$newPosts = array();
$modelSets = array();
$met = false;
while($row = $db->get_row($res2)) {
  // thread_id, uri, board_json
  $uri = $row['uri'];
  // it is possible a board gets deleted and leaves a reference in this table
  // so we can check another field in the pluck to ensure it exists...
  // we have a full join, so we're fine

  if (!isset($modelSets[$uri])) {
    $modelSets[$uri] = array(
      'posts' => getPostsModel($uri, array('checkBoard' => false)),
      'files' => getPostFilesModel($uri, array('checkBoard' => false)),
    );
  }

  // I think we basically have to N+1 this because of the tables...
  // otherwise we'd need a massive union
  $model = $modelSets[$uri];
  $posts = getThread($uri, $row['thread_id'], array(
    // weird unexpected results if we turn this off...
   'includeOP' => true,
    'posts_model' => $model['posts'],
    'post_files_model' => $model['files'],
  ));
  if (!$posts) {
    continue;
  }
  $op = $posts[0];
  if ($op['threadid']) {
    // if someone puts a replyid in here instead of thread, we can catch it
    //echo "There's a problem with ", $uri, ' ', $row['thread_id'], "<br>\n";
    continue;
  }
  // hide deleted threads where no replies
  // this is going to mess with the paging
  // got warning, why isn't deleted always set?
  if (count($posts) === 1 && !empty($op['deleted'])) {
    continue;
  }

  // so this query is bumped threads
  // but a thread might have all the recent posts tbh
  // so we should index them by timestamp
  // and check a minimum of 6 threads...
  // well that way we have to check them all tbh
  // we need a new base query
  $post = array_pop($posts);
  $post['boardUri'] = $uri;

  // if has images
  if (count($post['files'])) {
    foreach($post['files'] as $f) {
      $f['tno'] = $post['threadid'];
      $f['pno'] = $post['no'];
      $f['uri'] = $uri;
      $newFiles[]= $f;
    }
  }

  // if has text
  if ($post['com']) {
    $newPosts[]= $post;
  }
  // just grab the last 6
  if (count($newPosts) > 5 && count($newFiles) > 5) {
    $met = true;
    break;
  }
}
// free result?

$showShortlist = !empty($settings['site']['shortlistMode']);
$shortlist = array();
if ($showShortlist) {
  if (!empty($settings['site']['customBoardShortlistList'])) {
    $uris = preg_split('/, ?/', $settings['site']['customBoardShortlistList']);
    // maybe getBoards would be good
    //$shortlist_html = join("<br>", $uris);
    foreach($uris as $buri) {
      // FIXME: is it in boards?
      $row = getBoardRaw($buri);
      $shortlist[$buri] = $row;
    }
    $shortlist = pluck($shortlist, array('title', 'description'));
  }
  // customBoardShortlistList
}


// are we trying to be lynxchan compatible?
sendResponse(array(
  'boards' => $res,
  'shortlist' => $shortlist,
  'newPosts' => $newPosts,
  'newFiles' => $newFiles,
  'settings' => $settings,
  'debug' => array(
    'met' => $met,
  )
));

?>
