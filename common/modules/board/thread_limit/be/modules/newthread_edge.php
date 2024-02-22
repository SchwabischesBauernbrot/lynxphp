<?php

// archive.is

$params = $getModule();

$boardUri = $io['boardUri'];

$tid = $io['p']['threadid'];
if ($tid) {
  // ignore new replies
  return;
}
// new thread

$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

// get board's thread limit
$limit = empty($boardData['settings']['thread_limit']) ? 0 : $boardData['settings']['thread_limit'];

// if no limit, not need to get ocunt
if (!$limit) return;

// how many threads on this board?
$posts_model = getPostsModel($boardUri);
// this can be more simple...
$threadModel = getBoardThreadsModel($boardUri, $posts_model);
global $db;
// actually hard to make it more simple because of the deleted OP...
/*
$res = $db->find($posts_model, array('criteria' => array(
  'threadid' => 0,
  '
)))
*/
$threadCount = $db->count($threadModel);
if ($threadCount + 1 > $limit) {
  // get a list of threads
  $res = $db->find($threadModel, array(
    'orderNoAlias' =>'sticky desc, updated_at desc',
  ));
  $threads = $db->toArray($res);
  // splice to limit - 1 to make room for the new thread
  $removeThreads = array_slice($threads, $limit - 1);
  // queue delete of threads
  foreach($removeThreads as $row) {
    $pid = $row['postid'];
    echo "Recommend nuking thread[$pid]<br>\n";
    if (0) {
      requestDeleteThread($boardUri, $pid, array(
        'posts_model' => $posts_model,
      ));
    }
  }
}

?>