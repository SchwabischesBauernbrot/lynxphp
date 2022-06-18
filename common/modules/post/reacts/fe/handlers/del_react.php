<?php

$params = $getHandler();

$uri = $params['request']['params']['uri'];
$threadId = $params['request']['params']['threadId'];
$postId = $params['request']['params']['postId'];
//$react = $params['request']['params']['react'];

$result = $pkg->useResource('del_react', array(
  'boardUri' => $uri,
  'threadId' => $threadId,
  'postId'   => $postId,
  //'react'    => $react,
));

if ($result['success']) {
  // redirect back to the post
  global $BASE_HREF;
  redirectTo($BASE_HREF . $uri . '/thread/' . $threadId . '.html#' . $postId);
} else {
  wrapContent('React error: ' . print_r($result, 1));
}

?>