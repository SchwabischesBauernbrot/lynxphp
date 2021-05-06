<?php

$params = $getHandler();

$fields = $shared['fields']; // imported from shared.php
global $pipelines;
// handle hooks for additional settings
$pipelines[PIPELINE_BOARD_SETTING_GENERAL]->execute($fields);

// FIXME: get from formdata...
$res = processFiles();
// we can't do this because of the cookie settings...
//wrapContent('Please wait...');

$res = $pkg->useResource('save_settings', array(),
  array('addPostFields' => $_POST)
);

if (!empty($res['setCookie'])) {
  setcookie('session', $res['setCookie']['session'], $res['setCookie']['ttl'], '/');
}

if ($res['success']) {
  // maybe a js alert?
  echo "Success<br>\n";
  redirectTo('/user/settings');
} else {
  wrapContent('Something went wrong...' . print_r($res, 1));
}


?>
