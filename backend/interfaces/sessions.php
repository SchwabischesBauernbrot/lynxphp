<?php

// get user from session
function getUserID() {
  global $db, $models;
  $sid = empty($_SERVER['HTTP_SID']) ? '' : $_SERVER['HTTP_SID'];
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
