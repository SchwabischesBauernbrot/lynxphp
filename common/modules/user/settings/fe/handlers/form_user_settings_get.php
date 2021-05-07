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

$data = $pkg->useResource('settings');
$values = $data['settings'];
//echo '<pre>values[', print_r($values, 1), "]</pre>\n";

$html = generateForm($params['action'], $io['fields'], $values);

wrapContent(renderUserPortalHeader() . 'User Settings'. $html);

?>
