<?php

$params = $getModule();

$userSettings = $io['userSettings'];

$allThemes = $shared['themes'];

unset($allThemes['default']); // erase default as a possible final option
$themes = array_keys($allThemes);

// FIXME: set default theme in siteSettings
// translate default to first theme...
if (empty($userSettings['current_theme']) || $userSettings['current_theme'] === 'default') $userSettings['current_theme'] = $themes[0];

// make sure theme is valid
if (!empty($allThemes[$userSettings['current_theme']])) {
  // load theme
  $themesHtml = '<link id="theme" rel="stylesheet" data-theme="' . $userSettings['current_theme'] . '" href="css/themes/' . $userSettings['current_theme'] . '.css">';
}

/*
$themesHtml = '';
foreach($themes as $theme) {
  //echo $userSettings['current_theme'], '===', $theme, "<br>\n";
  if ($userSettings['current_theme'] === $theme) {
    $themesHtml .= '<link id="theme" rel="stylesheet" data-theme="' . $theme . '" href="css/themes/' . $theme . '.css">';
  } else {
    // these are always downloaded in chrome... ugh
    //$themesHtml .= '<link rel="alternate stylesheet" type="text/css" data-theme="' . $theme . '" title="' . $theme . '" href="css/themes/' . $theme . '.css">';
  }
}
*/

$io['head_html'] .= $themesHtml;

?>