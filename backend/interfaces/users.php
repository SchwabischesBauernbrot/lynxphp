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
  return in_array($group, $usergroups);
}


?>
