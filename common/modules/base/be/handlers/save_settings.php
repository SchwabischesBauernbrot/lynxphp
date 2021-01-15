<?php

$params = $get();

if (!userInGroupMiddleware($request, array('admin'))) {
  return;
}

global $db, $models;
$settings = array();
$settings['json'] = getAllSiteSettings();
//if (!is_array($settings['json'])) $settings['json'] = array();

//echo "<pre>GET[", print_r($_GET, 1), "]</pre>\n";
//echo "<pre>POST[", print_r($_POST, 1), "]</pre>\n";
//echo "<pre>FILES[", print_r($_FILES, 1), "]</pre>\n";

//echo "settings[", gettype($settings), "][", print_r($settings, 1), "]<br>\n";
//echo "json[", gettype($settings['json']), "][", print_r($settings['json'], 1), "]<br>\n";
foreach($_POST as $k => $v) {
  //echo "set [$k=$v]<br>\n";
  $settings['json'][$k] = $v;
}

$settings['json']['logo'] = false;
if ($_POST['logo']) {
  $logo = json_decode($_POST['logo'], true);
  //echo "<pre>logos[", print_r($logos, 1), "]</pre>\n";
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

//echo "settings[", gettype($settings), "][", print_r($settings, 1), "]<br>\n";
$ok = $db->update($models['setting'], $settings, array('criteria'=>array('settingid'=>1)));

sendResponse(array(
  'success' => $ok ? 'true' : 'false',
  //'settings' => $settings,
));

?>
