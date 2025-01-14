<?php

$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

//$fields = $shared['fields']; // imported from fe/common.php
$section = empty($params['request']['params']['section']) ? 'board' : $params['request']['params']['section'];
$fields = getBoardFields($section);

// handle hooks for additionl settings
//global $pipelines;
//$pipelines[PIPELINE_ADMIN_SETTING_GENERAL]->execute($fields);

// just pass all the _POST data to save_settings...
// maybe we could do some validation...
// or filter the params through the pipeline

// FIXME: get from formdata...
// this is a problem is something goes wrong...
$row = wrapContentData();
wrapContentHeader($row);
echo "Please wait...";

//print_r($_POST);

$res = $pkg->useResource('save_settings', array('boardUri' => $boardUri),
  array('addPostFields' => $_POST)
);

// only change it if we rename it in the _POST data
// since most settings won't change this
if (!empty($_POST['uri'])) {
  // only place in the system where the boardUri changes
  $boardUri = $_POST['uri'];
}

if ($res['success'] && $res['success'] !== 'false') {
  // maybe a js alert?
  echo "Success<br>\n";
  //wrapContentFooter($row);
  // redirect dev mode does it's own weird header thing...
  // FIXME: go back to the section if there was one...
  redirectTo('/' . $boardUri . '/settings/board.html', array('header' => false));
} else {
  //wrapContent();
  echo 'Something went wrong...' , print_r($res, 1);
  wrapContentFooter($row);
}

?>
