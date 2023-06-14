<?php

function getUserData() {
  //global $persist_scratch;
  //$key = 'user_session' . $_COOKIE['session'];
  //$user = $persist_scratch->get($key);
  $user_id = loggedIn();
  $userRes = getAccount($user_id);
  if (!$userRes) {
    return false;
  }
  $ownedBoards = userBoards($user_id);
  $groups = getUserGroups($user_id);
  return array('account' => array(
    'ownedBoards' => $ownedBoards,
    'publickey' => $userRes['publickey'],
  ), 'user_id' => $user_id);
}

function perms_isBO($boardUri) {
  $user_id = loggedIn();
  return isBO($boardUri, $user_id);
  //$ownedBoards = userBoards($user_id);
  //return isUserPermitted($user_id, 'b/' . $boardUri);
}

function perms_inGroups($groups) {
  $user_id = loggedIn();
  return userInGroup($user_id, $groups);
}

?>