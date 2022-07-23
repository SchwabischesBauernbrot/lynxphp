<?php

$params = $getHandler();

$fields = $common['fields']; // imported from fe/common.php

// handle hooks for additionl settings
global $pipelines;
$pipelines[PIPELINE_ADMIN_SETTING_GENERAL]->execute($fields);

// just pass all the _POST data to save_settings...
// maybe we could do some validation...
// or filter the params through the pipeline

// FIXME: get from formdata...
$res = processFiles();
$row = wrapContentData();
wrapContentHeader($row);
echo 'Please wait...';

$files = array();
if (!empty($res['handles']['logo'])) {
  $files = $res['handles']['logo'][0];
}
$fields = $common['fields']; // imported from fe/common.php
foreach($fields as $field => $d) {
  //if (substr($field, 0, 4) === 'show') {
  if ($d['type'] === 'checkbox') { // smarter
    $_POST[$field] = empty($_POST[$field]) ? false : true;
  }
}
//echo '<pre>files: ', print_r($files, 1), "</pre>\n";

$res = $pkg->useResource('save_settings', array('logo' => json_encode($files)),
  array('addPostFields' => $_POST)
);

if ($res['success']) {
  // maybe a js alert?
  echo "Success<br>\n";
  redirectTo('/admin/settings.html', array('header' => false));
} else {
  wrapContent('Something went wrong...' . print_r($res, 1), array('header' => false));
}

?>
