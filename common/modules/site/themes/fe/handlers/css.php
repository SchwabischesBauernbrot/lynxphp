<?php

$params = $getHandler();

$allThemes = $shared['themes'];

//unset($allThemes['default']); // erase default as a possible final option
$themes = array_keys($allThemes);

global $packages, $now;
// FIXME: fileSize would be good to know per theme
// can put a timer around this...
$result = $packages['user_setting']->useResource('settings');
//echo "<pre>", print_r($result, 1), "</pre>\n";
if (!$result) {
  $userSettings = array();
  // backend can't/didn't respond (in time)
  // not sure if this will work
  // retry
  sleep(1); // if we loop, lets not do it fast
  //header('location: css.php');
  redirectTo('css.php');
  return;
} else {
  $userSettings = $result['settings'];
}

// normalize $theme

// FIXME: set default theme in siteSettings
// translate default to first theme...
if (empty($userSettings['current_theme']) || $userSettings['current_theme'] === 'default') {
  $userSettings['current_theme'] = theme_getDefault();
}
$theme = $userSettings['current_theme'];

// manually handle the caching...

// loggedin, session (settings)
// fileSize / eTag
// since this is theme only, we might be able to rely on etag
// this does seem to sometimes cut down on bytes transferred
if (checkCacheHeaders(0, array(
  'contentType' => 'text/css',
  'etag' => $theme,
))) {
  // they have up to date data
  // avoid the diskio
  return;
}

// make sure theme is valid
$themesHtml = '';
if (!empty($allThemes[$theme])) {
  // load theme
  header('Content-type: text/css');
  // or we could redirect where the theme is always cacheable
  // it's not in the webroot though...
  readfile('../common/modules/site/themes/fe/css/' . $userSettings['current_theme'] . '.css');
} else {
  echo "Invalid theme[", $userSettings['current_theme'], "]<br>\n";
  echo "Valid themes: ", join(',', $themes), "<br>\n";
}

?>
