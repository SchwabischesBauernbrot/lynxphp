<?php

$params = $getHandler();

$data = $pkg->useResource('settings');
$values = $data['site'];
//echo "<pre>[", print_r($values, 1), "]</pre>\n";

global $pipelines;
$fields = $common['fields']; // imported from fe/common.php
// handle hooks for additionl settings
$pipelines[PIPELINE_ADMIN_SETTING_GENERAL]->execute($fields);

$html = generateForm($params['action'], $fields, $values);

wrapContent('Settings'. $html);

?>
