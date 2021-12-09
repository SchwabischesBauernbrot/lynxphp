<?php

$params = $getHandler();

$category = 'general';
if (isset($params['request']['params']['category'])) {
  $category = $params['request']['params']['category'];
}

global $pipelines;
if (!isset($shared['CategoryFields'][$category])) {
  return wrapContent(renderUserPortalHeader() .
    'Invalid user setting category<br>' . "\n");
}
$fields = $shared['CategoryFields'][$category]; // imported from shared.php

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
  redirectTo('/user/settings.html');
} else {
  wrapContent('Something went wrong...' . print_r($res, 1));
}


?>
