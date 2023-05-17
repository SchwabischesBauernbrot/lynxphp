<?php

// strings/be

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
$limit = $boardData['settings']['bump_limit'];
// FIXME: get thread's reply limit?

// if no limit, not need to get ocunt
if (!$limit) return;

// how many posts in thread?
$cnt = getThreadPostCount($boardUri, $tid);

if ($cnt > $limit) {
  $io['createPostOptions']['sage'] = true;
}

/*
$action = post_strings_getAction($io['p']['com']);

if ($action !== 0) {
  // FIXME: we need to log the IP and the post
  // rotate the posts...
}
*/

?>