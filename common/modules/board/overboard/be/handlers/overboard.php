<?php

// be

$params = $get();

global $db, $models;

// need to join board to get the settings
$ob_extended = $models['overboard_thread'];
$boardTable = modelToTableName($models['overboard_thread']);
if ($db->btTables) {
  $boardTable = '`' . $boardTable . '`';
}

$ob_extended['children'] = array(
  array(
    'model' => $models['board'],
    'pluck' => array('ALIAS.json as board_json'),
    'on' => array(
      array('uri', '=', $db->make_direct($boardTable . '.uri')),
    ),
  ),
);

$res = $db->find($ob_extended, array(
  'order' => 'updated_at desc',
  'limit' => 50, // FIXME: make adjustable
));

$boardSettings = array();
$threads = array();
$modelSets = array();
while($row = $db->get_row($res)) {
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

  if ($row['board_json'] && !isset($boardSettings[$uri])) {
    $json = json_decode($row['board_json'], true);
    if (!empty($json['settings'])) {
      $boardSettings[$uri] = $json['settings'];
    } else {
      $boardSettings[$uri] = array();
    }
  }

  // unlimited amount
  $thdPstCnt = count($posts);
  $thread = $op;
  $thread['boardUri'] = $uri;
  $thread['thread_reply_count'] = $thdPstCnt - 1; // op isn't a reply
  // post preview = 5
  // we want the last 5 posts, not the first 5
  // thread has the op and that contains these posts
  // we have to filter the out if it's included...
  if ($thdPstCnt > 6) {
    // we can't include the op, so we need at least 6 posts count
    $thread['posts'] = array_slice($posts, $thdPstCnt - 5, 5);
  } else {
    // just skip op, show the rest
    $thread['posts'] = array_slice($posts, 1);
  }
  $threads[]= $thread;
}
// free result?

sendResponse2(array(
  'threads' => $threads,
), array(
  'meta'=> array(
    'boardSettings' => $boardSettings,
  ),
));

?>
