<?php

require '../frontend_lib/lib/lib.listing.php'; // a component for SCRUD

$params = $getHandler();

$data = $pkg->useResource('settings');
$values = $data['site'];
//echo "<pre>[", print_r($values, 1), "]</pre>\n";

global $pipelines;
//echo "<pre>[", print_r($params, 1), "]</pre>\n";
$section = $params['request']['params']['section'];

$fields = getAdminFields($section);

// handle hooks for additionl settings
//$pipelines[PIPELINE_ADMIN_SETTING_GENERAL]->execute($fields);

//echo "<pre>fields:", print_r($fields, 1), "</pre>\n";
//echo "<pre>values:", print_r($values, 1), "</pre>\n";

if ($fields) {
  $html = generateForm($params['action'], $fields, $values);
} else {
  // FIXME: template
  $header = '<br>';
  //
  $footer = '';
  $template = array('header' => $header, 'footer' => $footer);
  $fields = array();
  $html = component_listing($template, '/admin/settings/' . $section . '/add', 'URL', $fields);
}

wrapContent(renderAdminPortal() . ucfirst($section) . ' Settings'. $html);

?>
