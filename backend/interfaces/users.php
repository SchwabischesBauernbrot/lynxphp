<?php

// create user
// log in

function userBoards($user_id) {
  global $db, $models;
  $user_id = (int)$user_id;
  $res = $db->find($models['board'], array('criteria'=>array(
    //array('owner_id', '=', $user_id),
    'owner_id' => $user_id
  )));
  $boards = $db->toArray($res);
  $db->free($res);
  $boardList = array();
  foreach($boards as &$row) {
    boardDBtoAPI($row);
    $boardList[] = $row['uri'];
  }
  unset($row);
  return $boardList;
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

function deleteUser($userid) {
  global $db, $models;
  // FIXME: sessions? usergroups? auth_challenges (on pubkey)?
  // boards? I don't think we should...
  return $db->deleteById($models['user'], $userid);
}

// I guess we moved these out?
function verifyChallengedSignatureHandler() {
  if (!hasPostVars(array('chal', 'sig'))) {
    // hasPostVars already outputs
    return;
  }
  $chal = $_POST['chal'];
  $sig  = $_POST['sig'];
  include '../common/sodium/autoload.php';
  // validate chal is one we issued? why?
  // so we can't reuse an old chal
  // well at least
  // FIXME: make sure it's not expired
  global $db, $models;
  $res = $db->find($models['auth_challenge'], array('criteria' =>
    array('challenge' => $chal)
  ));
  if (!$db->num_rows($res)) {
    $db->free($res);
    return sendResponse(array(), 401, 'challenge not found');
  }
  $row = $db->get_row($res);
  $db->free($res);
  // make sure no one can replay
  $db->deleteById($models['auth_challenge'], $row['challengeid']);
  $edPkBin = base64_decode($row['publickey']); // it's ed signing key in b64

  // prove payload was from user and not just a guessed challenge
  if (!\Sodium\crypto_sign_verify_detached($sig, $chal, $edPkBin)) {
    return sendResponse(array(), 401, 'signature verification failed');
  }
  return $edPkBin;
}

function loginResponseMaker($user_id, $upgradedAccount = false) {
  if (!$user_id) {
    return sendResponse(array(), 500, 'logging in as no user');
  }
  $sesrow = ensureSession($user_id);
  if (!isset($sesrow['created']) && $sesrow['userid'] != $user_id) {
    // there's already a session
    return sendResponse(array(), 400, 'You passed an active session');
  }
  // and return it
  $data = array(
    'session' => $sesrow['session'],
    'ttl'     => $sesrow['expires'],
    'upgradedAccount' => $upgradedAccount,
  );
  sendResponse($data);
}

?>