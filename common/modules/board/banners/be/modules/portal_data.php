<?php

$module = $getModule();

if (in_array('board', $io['portals'])) {

  // need to access request but all we got is response
  // response isn't going to have boardid
  $boardUri = $io['data']['board']['uri'];
  //echo "boardUri[$boardUri]<br>\n";

  //print_r($io);

  if (!isset($io['out']['boards'])) $io['out']['boards'] = array();

  $io['out']['board']['banners'] = getBannersByUri($boardUri);
}

?>
