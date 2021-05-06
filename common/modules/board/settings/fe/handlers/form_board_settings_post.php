<?php

$params = $getHandler();

$fields = $shared['fields']; // imported from fe/common.php

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

// handle hooks for additionl settings
global $pipelines;
$pipelines[PIPELINE_ADMIN_SETTING_GENERAL]->execute($fields);

// just pass all the _POST data to save_settings...
// maybe we could do some validation...
// or filter the params through the pipeline

// FIXME: get from formdata...
wrapContent('Please wait...');

$res = $pkg->useResource('save_settings', array('boardUri' => $boardUri),
  array('addPostFields' => $_POST)
);

// only place in the system where the boardUri changes
$boardUri = $_POST['uri'];

if ($res['success']) {
  // maybe a js alert?
  echo "Success<br>\n";
  redirectTo('/' . $boardUri . '/settings/board');
} else {
  wrapContent('Something went wrong...' . print_r($res, 1));
}

?>
