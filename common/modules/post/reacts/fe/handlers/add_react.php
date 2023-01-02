<?php

$params = $getHandler();

$uri = $params['request']['params']['uri'];
$threadId = $params['request']['params']['threadId'];
$postId = $params['request']['params']['postId'];
$react = $params['request']['params']['react'];

$result = $pkg->useResource('add_react', array(
  'boardUri' => $uri,
  'threadId' => $threadId,
  'postId'   => $postId,
  'react'    => $react,
));

// but why isn't success set?
if (isset($result['success']) && $result['success']) {
  // redirect back to the post
  global $BASE_HREF;
  redirectTo($BASE_HREF . $uri . '/thread/' . $threadId . '.html#' . $postId);
} else {
  wrapContent('React error: ' . print_r($result, 1));
}

?>