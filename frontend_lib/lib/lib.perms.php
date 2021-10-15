<?php

// abstraction layer for accessing user info

function isLoggedIn() {
  return isset($_COOKIE['session']);
}

/*
  // , array('inWrapContent'=>true)
  global $packages;
  $settings = $packages['base']->useResource('settings', false);
  // are we logged in?
  if (count($settings['user'])) {
    // works
    // are we the BO?
  }
*/

function getUserData() {
  global $scratch;
  $key = 'user_session' . $_COOKIE['session'];
  $user = $scratch->get($key);
  return $user;
}

// check our local user cache
function perms_getBoards() {
  // handles 401 badly...
  if (!isLoggedIn()) return false;

  global $scratch, $now;
  $key = 'user_session' . $_COOKIE['session'];
  $user = $scratch->get($key);
  //echo "<pre>", print_r($user, 1), "</pre>\n";
  // ensure $user['account']
  $getAccount = false;
  if ($user === false) $getAccount = true;
  else
  if (!isset($user['account'])) $getAccount = true;
  else
  if (isset($user['account_ts']) && $now - $user['account_ts'] > 60) {
    //echo "Old user data now[$now] [", $user['account_ts'], "]<br>\n";
    $getAccount = true;
  }
  if ($getAccount) {
    $account = backendLynxAccount();
    //echo '<pre>account[', print_r($account, 1), "</pre>\n";
    if ($account) {
      $user['account'] = $account;
      $user['account_ts'] = $now;
      $scratch->set($key, $user);
    } else {
      // backend problem? not parseable
      // either way we don't want invalid data in our cache...
    }
  }
  $account = $user['account'];
  $boards = empty($account['ownedBoards']) ? array() : $account['ownedBoards'];
  return $boards;
}

function perms_isBO($boardUri) {
  // handles 401 badly...
  if (!isLoggedIn()) return false;

  $myBoards = perms_getBoards();

  return in_array($boardUri, $myBoards);
}

?>