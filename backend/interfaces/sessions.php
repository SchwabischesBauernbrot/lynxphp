<?php

// by using a session, the frontend can purge it's secret ed key for nojs clients
// so system-wide signatures have to be optional and for jsonly
function createSession($userid) {
  global $now, $db, $models;
  $ttl = (int)$now + 86400; // 1 day from now
  // make sure session is unique
  $cnt = 1;
  while($cnt) {
    $session = md5(uniqid());
    $cnt = $db->count($models['session'], array('criteria' => array('session' => $session)));
  }

  $sesRow = array(
    'session' => $session,
    'user_id' => (int)$userid, // (postgres) requires it to be an int
    'expires' => $ttl,
    'ip'      => getip(),
  );
  $id = $db->insert($models['session'], array($sesRow));

  // schedule expiration check
  global $workqueue;
  // are we scheduling work on every request?
  $workqueue->addWork(PIPELINE_SESSION_EXPIRATION, array());

  // handle db failure
  if (!$id) {
    return false;
  }

  // if a BO or BV we might need to mark last login
  // we should bring the board record up-to-date now
  // so pipeline? is it optional?
  // we should be able to log it
  // so make a table and insert a login record here
  // either way, it's we should have a hook
  $loginLogRow = array(
    'user_id' => $sesRow['user_id'],
    'ip' => $sesRow['ip'],
  );
  $db->insert($models['login'], array($loginLogRow));

  global $pipelines;
  // don't need PRE/POST because after the record is created
  // we can update it after the fact
  // not much to change before hand, no validations or checks here
  $sesRow['id'] = $id;
  // is this always a login though
  // an empty session doesn't mean it's complete
  $pipelines[PIPELINE_USER_LOGIN]->execute($sesRow);

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

// I don't think anything uses this anymore
function sessionSetUserID($ses, $userid) {
  global $db, $models;
  // FIXME: expiration check?
  $cnt = $db->count($models['session'], array('criteria'=>array('session'=>$ses)));
  if (!$cnt) {
    return false;
  }
  return $db->update($models['session'], array('user_id' => $userid), array('criteria'=>array('session'=>$ses)));
}

function ensureSession($userid = 0) {
  global $now;
  // do we have a session?
  $sesRow = getSession();
  if (!$sesRow) {
    // create a session...
    $ses = createSession($userid);
    if (!$ses) {
      sendResponse2(array(), array(
        'code' => 500,
        'err' => 'Could not create session',
      )); // returns true
      return false; // have to return something falish
    }
    // info any future calls we have one
    $_SERVER['HTTP_SID'] = $ses['session'];
    // normalize
    $sesRow = getSession();
    // ttl is in expires
    $sesRow['created'] = (int)$now;
  }
  return $sesRow;
}

// get user from session
function getUserID() {
  $sesRow = getSession();
  if (!$sesRow) return $sesRow; // pass error through
  return $sesRow['user_id'];
}

// maybe too helpful...
// if this middleware condition failures, then returns this...
function loggedIn() {
  $userid = getUserID();
  if ($userid === 0) {
    // expired
    sendResponse2(array(), array(
      'code' => 401,
      'err' => 'No Session',
    )); // returns true
    return false; // have to return something falish
  }
  if (!$userid) {
    // session does not exist
    sendResponse2(array(), array(
      'code' => 401,
      'err' => 'Invalid Session',
    ));
    return false; // have to return something falish
  }
  return $userid;
}

function getIdentity() {
  $userid = getUserID(); // are we logged in?
  if ($userid) return 'user_' .  $userid;
  $sid = getServerField('HTTP_SID');
  // it doesn't matter if it's valid or expired
  // we just need something to track by
  return 'session_' . $sid;
}

?>