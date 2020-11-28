<?php

// create user
// log in

function userBoards($user_id) {
  global $db, $models;
  $user_id = (int)$user_id;
  $res = $db->find($models['board'], array('critera'=>array(
    'owner_id' => $user_id
  )));
  $boards = $db->toArray($res);
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

?>
