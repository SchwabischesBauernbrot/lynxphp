<?php

function createSession($userid) {
  global $now, $db, $models;
  $ttl = $now + 86400; // 1 day from now
  // make sure session is unique
  $cnt = 1;
  while($cnt) {
    $session = md5(uniqid());
    $cnt = $db->count($models['session'], array('criteria'=>array('session'=>$session)));
  }
  $id = $db->insert($models['session'], array(array(
    'session' => $session,
    'user_id' => $userid,
    'expires' => $ttl,
    'ip'      => getip(),
  )));
  if (!$id) {
    return false;
  }
  return array(
    'sessionid' => $id,
    'session'   => $session,
    'ttl'       => $ttl,
  );
}

function getSession($sid = '') {
  global $db, $models;
  if (!$sid) {
    $sid = getServerField('HTTP_SID');
  }
  if (!$sid) {
    return 0;
  }
  $sesRes = $db->find($models['session'], array('criteria' => array(
    array('session', '=', $sid),
  )));
  if (!$db->num_rows($sesRes)) {
    return null;
  }
  $sesRow = $db->get_row($sesRes);
  if (time() > $sesRow['expires']) {
    return false;
  }
  return $sesRow;
}

function sessionSetUserID($ses, $userid) {
  global $db, $models;
  // FIXME: expiration check?
  $cnt = $db->count($models['session'], array('criteria'=>array('session'=>$session)));
  if (!$cnt) {
    return false;
  }
  return $db->update($models['session'], array('user_id' => $userid), array('criteria'=>array('session'=>$session)));
}

function ensureSession($userid = 0) {
  global $now;
  // do we have a session?
  $sesRow = getSession();
  if (!$sesRow) {
    // create a session...
    $ses = createSession($userid);
    if (!$ses) {
      return sendResponse(array(), 500, 'Could not create session');
    }
    $_SERVER['HTTP_SID'] = $ses['session'];
    // normalize
    $sesRow = getSession();
    $sesRow['created'] = $now;
  }
  return $sesRow;
}

// get user from session
function getUserID() {
  $sesRow = getSession();
  if (!$sesRow) return $sesRow;
  return $sesRow['user_id'];
}

// maybe too helpful...
// if this middleware condition failures, then returns this...
function loggedIn() {
  $userid = getUserID();
  if ($userid === 0) {
    // expired
    sendResponse(array(), 401, 'No Session');
    return;
  }
  if ($userid === null) {
    // session does not exist
    sendResponse(array(), 401, 'Invalid Session');
    return;
  }
  if ($userid === false) {
    // expired
    sendResponse(array(), 401, 'Invalid Session');
    return;
  }
  return $userid;
}

?>
