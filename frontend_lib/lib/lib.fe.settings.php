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
  // make sure we have a cookie
  // can't return false because that's like userSettings not returning any data and it will retry
  if (!isLoggedIn()) return '';

  global $packages;
  if (!empty($packages['user_setting'])) {
    // so we only need to talk to the backend if we have a session
    // $user_id = loggedIn();

    // cacheable?
    $data = $packages['user_setting']->useResource('settings');
    //echo "<pre>", print_r($data, 1), "</pre>\n";
    // loggedIn (1/0)
    if (isset($data['loggedIn'])) {
      // communicate to lib.perms to reduce backend calls
      global $loggedIn;
      //echo "loggedin set";
      $loggedIn = $data['loggedIn'] ? 'true' : 'false';
    }
    // session ID string
    // settings: current_theme, name, postpass, volume, sitecustomcss, nsfw, hover, time
    if (isset($data['settings'])) {
      // this should be the login is valid?
      // /opt/session
      $userSettings = $data['settings'];
    }
    if (isset($data['account']['ownedBoards']) || isset($data['account']['groups'])) {
      global $persist_scratch, $now;
      $key = 'user_session' . $_COOKIE['session'];
      $user = $persist_scratch->get($key);
      // emulate full request
      $user['account_ts'] = $now;
      // why this?
      if (!isset($user['account']['meta'])) {
        $user['account']['meta']['code'] = 200;
      }
      // update the important data
      if (isset($data['account']['ownedBoards'])) {
        $user['account']['ownedBoards'] = $data['account']['ownedBoards'];
      }
      if (isset($data['account']['groups'])) {
        $user['account']['groups'] = $data['account']['groups'];
      }
      $persist_scratch->set($key, $user);
    }
  }
  return $userSettings;
}

?>