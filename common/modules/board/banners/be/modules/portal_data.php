<?php

$module = $getModule();

if (in_array('board', $io['portals'])) {

  // need to access request but all we got is response
  // response isn't going to have boardid
  if (isset($io['data']['board'])) {
    $boardUri = $io['data']['board']['uri'];
  } else
  if (isset($io['data']['uri'])) {
    $boardUri = $io['data']['uri'];
  }
  //echo "boardUri[$boardUri]<br>\n";

  //print_r($io);

  if (!isset($io['out']['boards'])) $io['out']['boards'] = array();

  $io['out']['board']['banners'] = getBannersByUri($boardUri);
}

?>
