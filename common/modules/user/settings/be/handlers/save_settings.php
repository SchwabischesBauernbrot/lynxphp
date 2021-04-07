<?php
$params = $get();

// are we logged in?
$userid = getUserID();

// load current settings?

// overwrite

// put into db
global $db, $models, $now;
$setCookie = NULL;
if ($userid) {
  // put it into our settings
  $userRow = getAccount($userid);
  $userRow['json'] = json_decode($userRow['json'], true);
  foreach($_POST as $k => $v) {
    $userRow['json']['settings_' . $k] = $v;
  }
  $ok = $db->updateById($models['user'], $userid, array('json'=>$userRow['json']));
} else {
  $sesRow = ensureSession();
  if ($sesRow['created'] === $now) {
    // not going to have a username to send
    $setCookie = array(
      'session' => $sesRow['session'],
      'ttl'     => $sesRow['expires'],
    );
  }
  // put it into our session
  $sesRow['json'] = json_decode($sesRow['json'], true);
  foreach($_POST as $k => $v) {
    $sesRow['json']['settings_' . $k] = $v;
  }
  $ok = $db->updateById($models['session'], $sesRow['sessionid'], array('json'=>$sesRow['json']));
}

sendResponse(array(
  'success' => $ok ? 'true' : 'false',
  'setCookie' => $setCookie,
));

?>