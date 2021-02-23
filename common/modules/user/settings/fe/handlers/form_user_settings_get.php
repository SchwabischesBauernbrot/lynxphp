<?php

$params = $getHandler();

global $pipelines;
$fields = $shared['fields']; // imported from shared.php
// handle hooks for additional settings
$pipelines[PIPELINE_BOARD_SETTING_GENERAL]->execute($fields);

$data = $pkg->useResource('settings');
$values = $data['settings'];

$html = generateForm($params['action'], $fields, $values);

wrapContent('User Settings'. $html);

?>
