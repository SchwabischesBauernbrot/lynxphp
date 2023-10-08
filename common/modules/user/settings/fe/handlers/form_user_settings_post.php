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
//$res = processFiles();
//$res = processPostFiles(); // uploads to backend
// FIXME: we're not doing anything with this data...

// we can't do this because of the cookie settings...
//wrapContent('Please wait...');

$res = $pkg->useResource('save_settings', array(),
  array('addPostFields' => $_POST)
);

if (!empty($res['setCookie'])) {
  setcookie('session', $res['setCookie']['session'], $res['setCookie']['ttl'], '/');
}
if (!empty($res['setCookies'])) {
  foreach($res['setCookies'] as $c) {
    if (isset($ct['ttl'])) {
      setcookie($c['key'], $c['val'], $c['ttl'], '/');
    } else {
      setcookie($c['key'], $c['val'], 0, '/');
    }
  }
}

if ($res['success']) {
  // maybe a js alert?
  echo "Success<br>\n";
  // sync localStorage to match settings if JS is loaded
  // maybe should be a json blob
/*
echo <<< EOB
<script>
// volume alreayd handed
// audio or video?
//setLocalStorage('loop', $loop)
</script>
EOB;
*/
  redirectTo('/user/settings.html');
} else {
  wrapContent('Something went wrong...' . print_r($res, 1));
}


?>
