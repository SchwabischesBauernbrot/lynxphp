<?php

$params = $getHandler();

$category = 'general';
if (isset($params['request']['params']['category'])) {
  $category = $params['request']['params']['category'];
}

global $pipelines;
if (!isset($shared['CategoryFields'][$category])) {
  return wrapContent(renderUserPortalHeader() .
    'Invalid user setting category<br>' . "\n");
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
//echo '<pre>values[', print_r($values, 1), "]</pre>\n";

$html = generateForm($params['action'], $io['fields'], $values);

wrapContent(renderUserPortalHeader() . 'User Settings'. $html);

?>
