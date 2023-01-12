<?php

// abstraction layer for accessing user info

// this doesn't mean their session is valid though
// more like seems to be loggedin...
// only used inside this function...
function isLoggedIn() {
  return isset($_COOKIE['session']);
}

function loggedIn() {
  // cache for this page load
  global $loggedIn;
  if ($loggedIn) {
    //echo "cache[$loggedIn]<br>\n";
    return $loggedIn === 'true';
  }
  if (!isLoggedIn()) return false;
  // have session
  $res = checkSession(); // this actually goes out and validates session
  if ($res && isset($res['meta']) && $res['meta']['code'] == 401) {
  //if ($res && isset($res['data']) && is_array($res['data']) && !count($res['data'])) {
    //echo "setting false<br>\n";
    $loggedIn = 'false';
    return false;
  }
  //echo "logged[" , gettype($res), print_r($res, 1), "]<br>\n";
  $loggedIn = 'true';
  return true;
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
  global $persist_scratch;
  $key = 'user_session' . $_COOKIE['session'];
  $user = $persist_scratch->get($key);
  return $user;
}

// check our local user cache
function perms_getBoards() {
  // handles 401 badly...
  if (!isLoggedIn()) return false;
  global $loggedIn;
  if ($loggedIn) {
    // don't bother checking if we already know we're logged out
    if ($loggedIn === 'false') return false;
  }
  static $accountCache = array();
  global $persist_scratch, $now;
  $key = 'user_session' . $_COOKIE['session'];
  //echo "key[$key]<br>\n";
  $user = $persist_scratch->get($key);
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
  if (isset($accountCache[$_COOKIE['session']])) {
    // prevent a bunch of calls to the backend if have an expired session
    $getAccount = false;
  }
  if ($getAccount) {
    // you can have a session and not be logged in
    $account = backendLynxAccount(false);
    //echo '<pre>account[', print_r($account, 1), "</pre>\n";
    if ($account) {
      $user['account'] = $account;
      $user['account_ts'] = $now;
      $persist_scratch->set($key, $user);
    } else {
      // backend problem? not parseable
      // either way we don't want invalid data in our cache...
      $accountCache[$_COOKIE['session']] = false;
    }
  }
  $account = empty($user['account']) ? '' : $user['account'];
  $boards = empty($account['ownedBoards']) ? array() : $account['ownedBoards'];
  return $boards;
}

function perms_isBO($boardUri) {
  // handles 401 badly...
  if (!isLoggedIn()) return false;
  // post_renderer calls this
  //echo gettrace();

  $myBoards = perms_getBoards();
  if ($myBoards === false) return false;
  return in_array($boardUri, $myBoards);
}

function perms_inGroups($groups) {
  // handles 401 badly...
  if (!isLoggedIn()) return false;
  // post_renderer calls this
  //echo gettrace();
  $user = getUserData();
  if (isset($user['account']['meta']) && $user['account']['meta']['code'] !== 200) {
    return false;
  }
  if ($user === false) return false;
  $usergroups = array();
  if (isset($user['account']['groups'])) {
    $usergroups = $user['account']['groups'];
  } else {
    if (DEV_MODE) {
      echo "<pre>DEV NOTE: no account.groups [", print_r($user, 1), "]</pre>\n";
    }
  }
  foreach($groups as $g) {
    if (!in_array($g, $usergroups)) return false;
  }
  return true;
}


?>