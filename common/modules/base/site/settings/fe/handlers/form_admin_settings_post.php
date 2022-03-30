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
wrapContent('Please wait...');

$files = array();
if (!empty($res['handles']['logo'])) {
  $files = $res['handles']['logo'][0];
}
//echo '<pre>files: ', print_r($files, 1), "</pre>\n";

$res = $pkg->useResource('save_settings', array('logo' => json_encode($files)),
  array('addPostFields' => $_POST)
);

if ($res['success']) {
  // maybe a js alert?
  echo "Success<br>\n";
  redirectTo('/admin/settings.html');
} else {
  wrapContent('Something went wrong...' . print_r($res, 1));
}

?>
