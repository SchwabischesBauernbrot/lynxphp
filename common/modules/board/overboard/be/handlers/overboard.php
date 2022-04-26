<?php
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
    //'posts_model' => $model['post'],
    //'post_files_model' => $model['files'],
  ));
  $threads[$i]['thread_reply_count'] = count($threads[$i]['posts']);
  // post previw = 5
  $threads[$i]['posts'] = array_slice($threads[$i]['posts'], 0, 5);
}

sendResponse2(array(
  'threads' => $threads,
));

?>
