<?php

// be

$params = $get();

// we have to look at ALL threads
$boards = listBoards();
// if there are a ton of boards this will be really slow...
$threadTimes = array();
foreach($boards as $i => $b) {
  //echo "<pre>b[", print_r($b, 1), "]</pre>\n";
  $uri = $b['uri'];
  $boards[$i]['threads'] = boardCatalog($uri);
  // make a list of threads
  foreach($boards[$i]['threads'] as $pageThreads) {
    //echo "<pre>t[", print_r($t, 1), "]</pre>\n";
    //if (!count($p)) continue; // no threads on this board
    foreach($pageThreads as $t) {
      // could push it off
      $time = $t['updated_at'];
      //echo "time[$time]<br>\n";
      if (!isset($threads[$time])) {
        $threadTimes[$time] = array();
      }

      //$thread = $t;
      //$thread['post'] = array_slice($t, 1);
      $t['boardUri'] = $uri;
      $threadTimes[$time][] = $t;
    }
  }
}
krsort($threadTimes);

$threads = array();
foreach($threadTimes as $times) {
  foreach($times as $t) {
    $threads[] = $t;
  }
}

// a limit
// FIXME paging?
$threads = array_slice($threads, 0, 50);

/*
$models = array();
foreach($threads as $i => $t) {
  if (!isset($models[$t['boardUri']])) {
    $models[$t['boardUri']] = array(
      'posts' => getPostsModel($t['boardUri']),
      'files' => getPostFilesModel($t['boardUri']),
    );
  }
}
*/

//print_r($threads);

$boardSettings = array();
foreach($threads as $i => $t) {
  $uri = $t['boardUri'];
  if (!isset($boardSettings[$uri])) {
    // anything we need to filter out?
    // do we just want/need the settings field?
    $boardData = getBoard($uri, array('jsonFields' => 'settings'));
    // settings isn't always set?
    $boardSettings[$uri] = empty($boardData['settings']) ? array() : $boardData['settings'];
  }
}

// now load in the posts
foreach($threads as $i => $t) {
  /*
  if (!isset($t['boardUri']) {
    echo "Threads missing boardUri\n";
  }
  if (!isset($models[$t['boardUri']])) {
    continue;
  }
  $model = $models[$t['boardUri']];
  */

  // there's tpp but it's like 10...
  $threads[$i]['posts'] = getThread($t['boardUri'], $t['no'], array(
    // weird unexpected results if we turn this off...
   'includeOP' => true,
    //'posts_model' => $model['post'],
    //'post_files_model' => $model['files'],
  ));
  $threads[$i]['thread_reply_count'] = count($threads[$i]['posts']);
  // post previw = 5
  // we want the last 5 posts, not the first 5
  $thdPstCnt = count($threads[$i]['posts']);
  // thread has the op and that contains these posts
  // we have to filter the out if it's included...
  if ($thdPstCnt > 6) {
    // we can't include the op, so we need at least 6 posts count
    $threads[$i]['posts'] = array_slice($threads[$i]['posts'], $thdPstCnt - 5, 5);
  } else {
    // just skip op, show the rest
    $threads[$i]['posts'] = array_slice($threads[$i]['posts'], 1);
  }
}

sendResponse2(array(
  'threads' => $threads,
), array(
  'meta'=> array(
    'boardSettings' => $boardSettings,
  ),
));

?>
