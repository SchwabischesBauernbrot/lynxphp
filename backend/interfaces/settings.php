<?php

function getUserSettings($userid = 'default') {
  //$setCookie = NULL;
  if (!is_numeric($userid)) {
    $userid = getUserID();
  }
  // are we logged in?
  if ($userid) {
    // put it into our settings
    $userRow = getAccount($userid);
    $settings = json_decode($userRow['json'], true);
  } else {
    $sesRow = getSession();
    /*
    global $now;
    $sesRow = ensureSession();
    if ($sesRow['created'] === $now) {
      // not going to have a username to send
      $setCookie = array(
        'session' => $sesRow['session'],
        'ttl'     => $sesRow['ttl'],
      );
    }
    */
    if ($sesRow) {
      $settings = json_decode($sesRow['json'], true);
    } else {
      // no session yet, so fresh session
      $sesRow = array('session' => '');
      $settings = array();
    }
  }

  // automatically hide anything not prefixed settings_
  $values = array();
  foreach($settings as $k => $v) {
    $pk = substr($k, 0, 9);
    if ($pk === 'settings_') {
      $nk = substr($k, 9);
      $values[$nk] = $settings[$k];
    }
  }

  return array(
    'settings' => $values,
    'loggedin' => $userid ? true : false,
    'session' => getServerField('HTTP_SID') ? getServerField('HTTP_SID') : $sesRow['session'],
    //'setCookie' => $setCookie,
  );
}

function getPublicSiteSettings() {
  global $db, $models;
  $row1 = $db->findById($models['setting'], 1);
  // create ID 1 if needed
  if ($row1 === null) {
    $db->insert($models['setting'], array(
      // 'settingid'=>1,
      array('changedby' => 0),
    ));
    $row1 = array('json' => '[]');
  }
  $settings = json_decode($row1['json'], true);
  return $settings;
}

function getAllSiteSettings() {
  global $db, $models;
  $settings = $db->findById($models['setting'], 1);
  // create ID 1 if needed
  if ($settings === false) {
    $db->insert($models['setting'], array(
      // 'settingid'=>1,
      array('changedby' => 0),
    ));
    $settings = array('json' => '[]', 'changedby' => 0, 'settingsid' => 1);
  }
  return json_decode($settings['json'], true);
}


?>
