<?php

$params = $getModule();

// io has siteSettings, userSettings and head_html
// params has options (which is empty)

$sheet = '/user/settings/theme.php'; // dynamic

// demo doesn't need the user's theme, its setting it itself
if (strpos($_SERVER['REQUEST_URI'], '/themedemo/') !== false) {
  $sheet = 'css/themes/' . $io['userSettings']['current_theme'] . '.css';
}

// this can have it's own cache without affecting the php or static
$io['head_html'] .= '<link id="theme" rel="stylesheet" href="' . $sheet . '">';

/*
$userSettings = $io['userSettings'];

$allThemes = $shared['themes'];

unset($allThemes['default']); // erase default as a possible final option
$themes = array_keys($allThemes);

if (IN_GENERATE) {
  // reduce permutations and yet keep it reasonable
  // we can 304 to reduce bandwidth
  // but will still eat php workers...
  $io['head_html'] .= '<link id="theme" rel="stylesheet" href="dynamic.php?action=css_theme">';
  return;
}

// FIXME: set default theme in siteSettings
// translate default to first theme...
if (empty($userSettings['current_theme']) || $userSettings['current_theme'] === 'default') $userSettings['current_theme'] = $themes[0];

// make sure theme is valid
$themesHtml = '';
if (!empty($allThemes[$userSettings['current_theme']])) {
  // load theme
  $themesHtml = '<link id="theme" rel="stylesheet" data-theme="' . $userSettings['current_theme'] . '" href="css/themes/' . $userSettings['current_theme'] . '.css">';
} else {
  echo "Invalid theme[", $userSettings['current_theme'], "]<br>\n";
  echo "Valid themes: ", join(',', $themes), "<br>\n";
}

/*
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

//$io['head_html'] .= $themesHtml;

?>