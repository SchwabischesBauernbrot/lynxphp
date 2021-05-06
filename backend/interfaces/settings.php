<?php

function getUserSettings($userid = 'default') {
  //$setCookie = NULL;
  if (!is_numeric($userid)) {
    $userid = getUserID();
  }
  //echo "userid[$userid]<br>\n";
  // are we logged in?
  if ($userid) {
    // put it into our settings
    $userRow = getAccount($userid);
    if (!$userRow) {
      // user record deleted or not found
      return array(
        'settings' => array(),
        'loggedin' => false,
        'session'  => '',
      );
    }
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
  if (!$row1) {
    $db->insert($models['setting'], array(
      // 'settingid'=>1,
      array('changedby' => 0),
    ));
    $row1 = array('json' => '[]');
  }
  $settings = json_decode($row1['json'], true);
  return $settings;
}

// tough it would be better to only request them when needed
// and then being granular so that we can cache each resource
function getSettings() {
  // need a single row from setting
  // session => user single row load json field
  $user = getUserSettings();
  return array(
    'site' => getPublicSiteSettings(),
    'user' => $user['settings'],
  );
}

function getAllSiteSettings() {
  global $db, $models;
  $settings = $db->findById($models['setting'], 1);
  // create ID 1 if needed
  if (!$settings) {
    $db->insert($models['setting'], array(
      // 'settingid'=>1,
      array('changedby' => 0),
    ));
    $settings = array('json' => '[]', 'changedby' => 0, 'settingsid' => 1);
  }
  return json_decode($settings['json'], true);
}


?>
