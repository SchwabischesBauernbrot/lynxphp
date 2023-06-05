<?php

$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

// why isn't $boardData = getBoard($boardUri); good enough?
$values = $pkg->useResource('list', array('boardUri' => $boardUri));

$settings = $values['settings'];

global $pipelines;
//$fields = $shared['fields']; // imported from shared.php
$section = empty($params['request']['params']['section']) ? 'board' : $params['request']['params']['section'];

$fields = getBoardFields($section);

// handle hooks for additional settings
//$pipelines[PIPELINE_BOARD_SETTING_GENERAL]->execute($fields);

// fields will keep the settings_ prefix
// so we need to flatten our settings
if (isset($values['settings']) && is_array($values['settings'])) {
  foreach($values['settings'] as $k => $v) {
    $values['settings_' . $k] = $v;
  }
}
unset($values['settings']);

// FIXME: process defaults from boardSettings?

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

// strip settings_ off
foreach($values as $k => $v) {
  if (substr($k, 0, 9) === 'settings_') {
    $sk = substr($k, 9);
    $values[$sk] = $v;
  }
}
//echo '<pre>', htmlspecialchars(print_r($values, 1)), "</pre>\n";

$html = generateForm($params['action'], $fields, $values);

//$portal = getBoardSettingsPortal($boardUri, false, array(
//  'boardSettings' => $settings,
//));
// $portal['header'] .  . $portal['footer']
wrapContent('Board Settings' . $html);

?>
