<?php

$params = $get();

if (!userInGroupMiddleware($request, array('admin'))) {
  return;
}

global $db, $models;
$settings = array();
$settings['json'] = getAllSiteSettings();

$ok = true;

foreach($_POST as $k => $v) {
  if ($k === 'logo') {
  } else
  if ($k === 'logo_clear') {
    $settings['json']['logo'] = false;
  } else {
    $settings['json'][$k] = $v;
  }
}

if ($_POST['logo']) {
  $logo = json_decode($_POST['logo'], true);
  if (!empty($logo['hash'])) {
    $srcPath = 'storage/tmp/'.$logo['hash'];
    if (file_exists($srcPath)) {
      $arr = explode('.', $logo['name']);
      $ext = end($arr);
      $finalPath = 'storage/site/logo.png';
      copy($srcPath, $finalPath);
      unlink($srcPath);
      // would size be good?
      $settings['json']['logo'] = 'storage/site/logo.png';
    }
  }
}

$ok = $db->update($models['setting'], $settings, array('criteria'=>array('settingid'=>1)));

sendResponse(array(
  'success' => $ok ? 'true' : 'false',
));

?>
