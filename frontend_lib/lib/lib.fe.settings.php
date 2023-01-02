<?php

function settingsGetSections() {
  $adminSettings = getCompiledSettings('admin');

  //print_r($adminSettings);
  foreach($adminSettings as $nav => $ni) {
    //echo "<li>", $nav;
  }

}

function getUserSettings() {
  global $userSettings;
  if ($userSettings) return $userSettings;

  global $packages;
  if (!empty($packages['user_setting'])) {
    // cachable?
    $data = $packages['user_setting']->useResource('settings');
    //echo "<pre>", print_r($data, 1), "</pre>\n";
    // loggedIn (1/0)
    // session ID string
    // settings: current_theme, name, postpass, volume, sitecustomcss, nsfw, hover, time
    if (isset($data['settings'])) {
      $userSettings = $data['settings'];
    }
  }
  return $userSettings;
}

?>