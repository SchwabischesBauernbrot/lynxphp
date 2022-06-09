<?php

$module = $getModule();

if (!$io['addToPostsDB']) {
  // we're already not adding this post to the db for some reason
  // so no need to queue it
  return;
}

$boardUri = $io['boardUri'];
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

$action = post_strings_getAction($io['p']['com']);

if ($action !== 0) {
  $io['addToPostsDB'] = false;
  $io['returnId'] = array(
    'status' => 'refused',
  );
  // FIXME: we need to log the IP and the post
  // rotate the posts...
}

?>