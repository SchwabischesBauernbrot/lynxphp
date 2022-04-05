<?php

$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

$values = $pkg->useResource('list', array('boardUri' => $boardUri));

global $pipelines;
$fields = $shared['fields']; // imported from fe/common.php

// handle hooks for additional settings
//$pipelines[PIPELINE_BOARD_SETTING_QUEUEING]->execute($fields);

// fields will keep the settings_ prefix
// so we need to flatten our settings
if (isset($values['settings']) && is_array($values['settings'])) {
  foreach($values['settings'] as $k => $v) {
    $values['settings_' . $k] = $v;
  }
}
unset($values['settings']);

/*
foreach($fields as $fn => $f) {
  if (substr($fn, 0, 9) === 'settings_') {
    //$k = substr($fn, 9);
    //echo "k[$k]<br>\n";
    if (isset($values['json'][$fn])) {
      $values[$fn] = $values['json'][$fn];
    }
  }
}
*/

$html = generateForm($params['action'], $fields, $values);

wrapContent('Board Settings'. $html);

?>
