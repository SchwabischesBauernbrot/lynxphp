<?php

// FIXME: we need access to package
$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;
//$templates = moduleLoadTemplates('banner_detail', __DIR__);
//$tmpl = $templates['header'];

global $boardData;
if (empty($boardData)) {
  $boardData = getBoard($boardUri);
}

// wrap form
$tmpl = '';

$fields = array(
  'name' => array('label' => 'React name', 'type' => 'text'),
  'text' => array('label' => 'Text React', 'type' => 'text'),
  'image' => array('label' => 'Image React', 'type' => 'image'),
  'lock_default' => array('label' => 'Lock by Default', 'type' => 'checkbox'),
  'hide_default' => array('label' => 'Hide by Default', 'type' => 'checkbox'),
);
$values = array();

$tmpl = generateForm($params['action'], $fields, $values, array(
  'buttonLabel' => 'Add custom react'
));
// pop up fields...

$boardHeader = renderBoardSettingsPortalHeader($boardUri, $boardData);

wrapContent($boardHeader . $tmpl);

?>
