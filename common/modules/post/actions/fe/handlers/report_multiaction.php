<?php

$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

// FIXME: banPosterSelected
// FIXME: banReportSelected

// report-{{_id}}
$ids = array();
foreach($_POST as $k => $v) {
  if (substr($k, 0, 6) === 'report') {
    $ids[] = $k;
  }
}

if (!count($ids)) {
  return wrapContent("You must select at least one report, go back and try again!");
}
if (!$_POST['closeSelected'] && $_POST['deleteSelected']) {
  return wrapContent("You must select at least one action to perform, go back and try again!");
}

wrapContent("Processing request... please wait");

if ($_POST['closeSelected']) {
  $result = $pkg->useResource('close_reports', array(
      'boardUri' => $boardUri,
      'banTarget' => 0,
      'closeAllFromReporter' => false,
      'deleteContent' => $_POST['deleteSelected'] ? true : false,
    ),
    array('addPostFields' => $ids)
  );
} else {
  if ($_POST['deleteSelected']) {
    // delete single or multiple?..
    // lookup report...
    $result = $pkg->useResource('open_reports', array('boardUri' => $boardUri));
    // get reports that we're interested in
    $reports = array_filter($result['reports'], function($r) use ($ids) {
      return in_array('report-'.$r['_id'], $ids);
    });
    // build dataset to delete what we want...
    $nukes = array_map($reports, function($r) {
      return array($boardUri.'-ThreadNum-'.$r['postId'] => true);
    });

    $result = $pkg->useResource('content_actions',
      array('action' => 'delete', 'password' => $_POST['postpassword']),
      array('addPostFields' => $nukes)
    );
  }
}

if ($result['success'] === 'ok') {
  // redirect
  redirectTo('/'. $boardUri . '/settings/reports');
}

?>
