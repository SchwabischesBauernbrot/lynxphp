<?php

function getPublicSiteSettings() {
  global $db, $models;
  $row1 = $db->findById($models['setting'], 1);
  // create ID 1 if needed
  if ($row1 === false) {
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
