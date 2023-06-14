<?php

// autosage/be

$module = $getModule();

if (!$io['addToPostsDB']) {
  // we're already not adding this post to the db for some reason
  // so no need to refuse it
  return;
}

// which thread
$tid = $io['p']['threadid']; // will be 0 if new thread
if (!$tid) {
  return;
}

$boardUri = $io['boardUri'];
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

//print_r($boardData['settings']);

// get board's reply limit
$limit = empty($boardData['settings']['bump_limit']) ? 0 : $boardData['settings']['bump_limit'];
// FIXME: get thread's reply limit?

// if no limit, not need to get ocunt
if (!$limit) return;

// how many posts in thread?
$cnt = getThreadPostCount($boardUri, $tid);

if ($cnt > $limit) {
  // for replies only, this is what likely matters here
  $io['bumpThread'] = false;
  // for threads and replies
  // but probably should keep it in sync
  // we definitely want to bump the board
  // shows activity on the boards list
  // and won't affect the thread sorting
  //$io['createPostOptions']['bumpBoard'] = false;
}

/*
$action = post_strings_getAction($io['p']['com']);

if ($action !== 0) {
  // FIXME: we need to log the IP and the post
  // rotate the posts...
}
*/

?>