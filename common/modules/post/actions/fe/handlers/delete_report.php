<?php

$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

$id = $request['params']['id'];

wrapContent("Processing request... please wait");
$result = $pkg->useResource('close_reports', array(
    'boardUri' => $boardUri,
    'banTarget' => 0,
    'closeAllFromReporter' => false,
    'deleteContent' => true,
  ),
  array('addPostFields' => array('report-'.$id => true))
);

if ($result['success'] === 'ok') {
  // nice to confirm removedPosts isn't 0...
  // redirect
  redirectTo('/'. $boardUri . '/settings/reports');
} else {
  echo "Error: ", print_r($result, 1);
}

?>
