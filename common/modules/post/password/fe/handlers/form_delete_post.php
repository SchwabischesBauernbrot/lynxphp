<?php

$params = $getHandler();

//echo "<pre>", print_r($params, 1), "</pre>\n";
$p = $params['request']['params'];
$uri = $p['uri'];
$pno = $p['pno'];

$password = getOptionalPostField('password');

$dbg = array(
  'uri' => $uri,
  'pno' => $pno,
  'password' => $password,
);

// board-threadnum-postnum is the name...
$postFields = array($uri.'-ThreadNum-'.$pno => true);

// request the deletion
global $packages;
$result = $packages['post_actions']->useResource('content_actions', array('action' => 'delete', 'board' => $uri, 'id' => $pno, 'password' => $password), array('addPostFields' => $postFields));

//$result = $pkg->useResource('delete', array('uri' => $uri, 'pno' => $pno, 'password' => $password));
// removedPosts is in debug

$boardUri = $uri;
// FIXME overboard support
// FIXME multiple threads, differing? no really...
$threadNum = $result['request'][0]['threadid'];

$removedPosts = empty($result['removedPosts']) ? 0 : $result['removedPosts'];
$removedThreads = empty($result['removedThreads']) ? 0 : $result['removedThreads'];
//echo "removedPosts[$removedPosts] count[", count($postFields), "]<br>\n";
//if ($removedPosts + $removedThreads === count($postFields)) {
if ($result['status'] === 'ok') {
  echo "Successful!<bR>\n"; flush();


  if (!empty($_POST['page'])) {
    return redirectTo('/' . $boardUri . '/page/' . $_POST['page']);
  } else
  if ($threadNum === 'ThreadNum') {
    return redirectTo('/' . $boardUri . '/');
  } else {
    // FIXME: if it's a thread with no replies, then we should redirect back to the catalog
    if ($removedThreads) {
      return redirectTo('/' . $boardUri);
    }
    return redirectTo('/' . $boardUri . '/thread/' . $threadNum . '.html');
  }
} else {

  if ($result['status'] === 'error') {
    // could be a 400 or 500
    http_response_code(410); // for gone (since it'll like be expired captcha)
    // would be nice to valid the captcha before going back
    // well that's a JS thing
    // for nojs, we just need to re-present the posts and form
    // or just ask for another captcha...
    //wrapContent('ERROR: ' . print_r($result, 1));
    wrapContent('ERROR: ' . $result['data']);
    return;
  }
  
  http_response_code(410); // for gone (since it'll like be expired captcha)
  wrapContent('RESULT ERROR: ' . print_r($result, 1));
}
