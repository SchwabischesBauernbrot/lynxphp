<?php

$params = $getHandler();

$fields = $shared['fields']; // imported from shared.php
global $pipelines;
// handle hooks for additional settings
$pipelines[PIPELINE_BOARD_SETTING_GENERAL]->execute($fields);

// FIXME: get from formdata...
$res = processFiles();
wrapContent('Please wait...');

$res = $pkg->useResource('save_settings', array(),
  array('addPostFields' => $_POST)
);

if ($res['success']) {
  // maybe a js alert?
  echo "Success<br>\n";
  redirectTo('/user/settings');
} else {
  wrapContent('Something went wrong...' . print_r($res, 1));
}


?>
