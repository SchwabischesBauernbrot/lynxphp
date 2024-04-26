<?php

$params = $getHandler();

//$fields = $common['fields']; // imported from fe/common.php

//echo "<pre>[", print_r($params, 1), "]</pre>\n";
$section = $params['request']['params']['section'];
$fields = getAdminFields($section);

// handle hooks for additionl settings
global $pipelines;
//$pipelines[PIPELINE_ADMIN_SETTING_GENERAL]->execute($fields);

// just pass all the _POST data to save_settings...
// maybe we could do some validation...
// or filter the params through the pipeline

// FIXME: get from formdata...
//$res = processFiles();
//$res = processPostFiles();
$res = processFilesVar(array('logo'));

$row = wrapContentData();
wrapContentHeader($row);
echo 'Please wait...';

//echo "<pre>files[", print_r($res, 1), "]</pre>\n";
// $res['logo'][0]['error', 'debug']
// $res['logo'][0]['tmp_name', 'type', 'name']

$files = array();
if (!empty($res['logo'][0])) {
  $f = $res['logo'][0];
  // no error OR if error make sure it's no field...
  if (empty($f['error']) || $f['error'] !== 'no field') {
    $res = sendFile($f['tmp_name'], $f['type'], $f['name']);
    //echo "<pre>files[", print_r($res, 1), "]</pre>\n";
    $files = $res['data'];
  }
}

if (0) {
  // FIXME: undefined
  $fields = $common['fields']; // imported from fe/common.php
  foreach($fields as $field => $d) {
    //if (substr($field, 0, 4) === 'show') {
    if ($d['type'] === 'checkbox') { // smarter
      $_POST[$field] = empty($_POST[$field]) ? false : true;
    }
  }
  //echo '<pre>files: ', print_r($files, 1), "</pre>\n";
}

$res = $pkg->useResource('save_settings', array('logo' => json_encode($files)),
  array('addPostFields' => $_POST)
);

if ($res['success']) {
  // maybe a js alert?
  echo "Success<br>\n";
  redirectTo('/admin.php', array('header' => false));
} else {
  wrapContent('Something went wrong...' . print_r($res, 1), array('header' => false));
}

?>
