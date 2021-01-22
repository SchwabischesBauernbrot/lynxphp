<?php

$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

$values = $pkg->useResource('list', array('boardUri' => $boardUri));

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

wrapContent('Board Settings'. $html);

?>
