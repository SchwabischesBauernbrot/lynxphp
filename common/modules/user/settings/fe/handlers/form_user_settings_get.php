<?php

$params = $getHandler();

$category = 'general';
if (isset($params['request']['params']['category'])) {
  $category = $params['request']['params']['category'];
}

global $pipelines;
if (!isset($shared['CategoryFields'][$category])) {
  return wrapContent(renderUserPortalHeader() .
    'Invalid user setting category [' . $category . ']<br>' . "\n");
}
$fields = $shared['CategoryFields'][$category]; // imported from shared.php

// handle hooks for additional settings
$io = array(
  'category' => $category,
  'fields' => $fields,
  // could pass values
);
$pipelines[PIPELINE_MODULE_USER_SETTINGS_FIELDS]->execute($io);

// might be better to just call /opt/settings
// since the wrap will need all that data anyways
$data = $pkg->useResource('settings');
$values = $data['settings'];

// if not cookie, we can use $data['session'] to set it...

// user doesn't have a session coookie
if (!isset($_COOKIE['session'])) {
  global $now;
  setcookie('session', $data['session'], (int)$now + 86400, '/');
}

// while it sets it, it's just ackward
/*
// this should be here, this should be done anytime we need settings...
if (!count($values)) {
  if (DEV_MODE) echo "DEV_MODE: loading in defaults<br>\n";
  foreach($io['fields'] as $f => $v) {
    if (isset($v['default'])) {
      $values[$f] = $v['default'];
    } else {
      if (DEV_MODE) echo "DEV_MODE: no default for [$f]<br>\n";
    }
  }
}
*/

//echo '<pre>fields[', print_r($io['fields'], 1), "]</pre>\n";
/*
foreach($io['fields'] as $f => $v) {
  if (isset($v['default'])) {
    if (!isset($values[$f])) {
      if (DEV_MODE) echo "DEV_MODE: loaded default for [$f]<br>\n";
      $values[$f] = $v['default'];
    }
  } else {
    if (DEV_MODE) echo "DEV_MODE: no default for [$f]<br>\n";
  }
}
*/
//echo '<pre>values[', print_r($values, 1), "]</pre>\n";

$html = generateForm($params['action'], $io['fields'], $values);

wrapContent(renderUserPortalHeader() . 'User Settings'. $html);

?>
