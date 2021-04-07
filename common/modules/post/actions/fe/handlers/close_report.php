<?php

$params = $getHandler();

// backend has to check the permissons one way or another
// lets not check it twice
/*
$res = backendGetPerm('close_report');
if (!$res['access']) {
  wrapContent('Access Denied');
  echo "<pre>Result [", gettype($res), print_r($res, 1), "]</pre>\n";
  return;
}
*/

if (empty($_GET['boardUri'])) {
  return wrapContent('boardUri is required');
}

$id = $request['params']['id'];

$boardUri = $_GET['boardUri'];

wrapContent("Processing request... please wait");
$params = array(
  'banTarget' => 0,
  'closeAllFromReporter' => false,
  'deleteContent' => false,
  'boardUri' => $boardUri,
);
$result = $pkg->useResource('close_reports', $params,
  array('addPostFields' => array('report-'.$id => true))
);

//echo "<pre>", print_r($result, 1), "</pre>\n";

//echo '<a href="/globals/reports">Global report</a>';
//return;

if ($result['success'] === 'ok') {
  redirectTo('/'. $boardUri . '/settings/reports');
} else {
}

?>
