<?php

$params = $getHandler();

// FIXME: are we logged in?

$values = $pkg->useResource('settings');

global $pipelines;
$fields = $shared['fields']; // imported from fe/common.php

foreach($fields as $fn => $f) {
  if (substr($fn, 0, 9) === 'settings_') {
    //$k = substr($fn, 9);
    //echo "k[$k]<br>\n";
    $values[$fn] = $values['json'][$fn];
  }
}

// handle hooks for additional settings
$pipelines[PIPELINE_BOARD_SETTING_GENERAL]->execute($fields);

$html = generateForm($params['action'], $fields, $values);

wrapContent('User Settings'. $html);

?>
