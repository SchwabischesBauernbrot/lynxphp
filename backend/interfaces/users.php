<?php

// create user
// log in

function userBoards($user_id) {
  global $db, $models;
  $user_id = (int)$user_id;
  $res = $db->find($models['board'], array('criteria'=>array(
//    array('owner_id', '=', $user_id),
    'owner_id' => $user_id
  )));
  $boards = $db->toArray($res);
  $db->free($res);
  foreach($boards as &$row) {
    boardDBtoAPI($row);
  }
  unset($row);
  return $boards;
}

function getAccount($user_id) {
  global $db, $models;
  $user_id = (int)$user_id;
  $user = $db->findById($models['user'], $user_id);
  return $user;
}

function getUserGroups($user_id) {
  global $db, $models;
  $models['usergroup']['parents'] = array(
    array(
      //'type' => 'left',
      'model' => $models['group'],
    )
  );
  $res = $db->find($models['usergroup'], array('criteria'=>array(
    'userid' => $user_id,
  )));
  $groups = pluck($db->toArray($res), 'name');
  $db->free($res);
  return $groups;
}

function userInGroup($user_id, $group) {
  $usergroups = getUserGroups($user_id);
  if (is_array($group)) {
    foreach($group as $g) {
      if (in_array($g, $usergroups)) return true;
    }
  }
  return in_array($group, $usergroups);
}

// allow threadNum to be 0
// p/boardUri/threadNum/postId
function isUserPermitted($user_id, $permission, $target = false) {
  // is user a admin or global?
  $isAdmin  = userInGroup($user_id, 'admin');
  $isGlobal = userInGroup($user_id, 'global');
  if ($isAdmin || $isGlobal) {
    return true;
  }

  $access = false;
  // does target object include boardUri (check for BO)
  if ($target) {
    $parts = explode('/', $target);
    //
    if ($parts[0] === 'b') {
      $boardUri = $parts[1];
      // BOs can do anything...
      $access = isBO($boardUri, $user_id);
      if (!$access) {
        // password match post password?
      }
    } else
    if ($parts[0] === 'p') {
      $boardUri = $parts[1];
      $access = isBO($boardUri, $user_id);
      if (!$access) {
        // password match post password?
      }
    }
  }

  return $access;
}

?>
