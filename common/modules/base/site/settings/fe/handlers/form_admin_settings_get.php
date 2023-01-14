<?php

$params = $getHandler();

$data = $pkg->useResource('settings');
$values = $data['site'];
//echo "<pre>[", print_r($values, 1), "]</pre>\n";

global $pipelines;
//echo "<pre>[", print_r($params, 1), "]</pre>\n";
$section = $params['request']['params']['section'];

$adminSettings = getCompiledSettings('admin');
if (isset($adminSettings[$section])) {
  $fields = $adminSettings[$section];
} else {
  $fields = $common['fields']; // imported from fe/common.php
}

// handle hooks for additionl settings
//$pipelines[PIPELINE_ADMIN_SETTING_GENERAL]->execute($fields);

//echo "<pre>fields:", print_r($fields, 1), "</pre>\n";
//echo "<pre>values:", print_r($values, 1), "</pre>\n";

$html = generateForm($params['action'], $fields, $values);

wrapContent(renderAdminPortal() . ucfirst($section) . ' Settings'. $html);

?>
