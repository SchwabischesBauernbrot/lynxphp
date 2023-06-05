<?php

$module = $getModule();

if (in_array('board', $io['portals'])) {

  // need to access request but all we got is response
  // response isn't going to have boardid
  $boardUri = false;
  //echo "<pre>", htmlspecialchars(print_r($io['out'], 1)), "</pre>\n";
  if (isset($io['out']['board']['uri'])) {
    $boardUri = $io['out']['board']['uri'];
  } else
  if (isset($io['data']['board'])) {
    $boardUri = $io['data']['board']['uri'];
  } else
  if (isset($io['data']['uri'])) {
    $boardUri = $io['data']['uri'];
  }
  //echo "boardUri[$boardUri]<br>\n";

  //print_r($io);

  // boards not board?
  if (!isset($io['out']['boards'])) $io['out']['boards'] = array();

  if ($boardUri) {
    $io['out']['board']['banners'] = getBannersByUri($boardUri);
  } else {
    $io['out']['board']['banners'] = array();
  }
}

?>
