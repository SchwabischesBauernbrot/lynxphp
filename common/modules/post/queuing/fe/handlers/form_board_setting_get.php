<?php

$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

// well we need a list of post_tags and their values...
$data = $pkg->useResource('get_settings', array('boardUri' => $boardUri));

$values = $data['values'];

$fields = array();
foreach($data['tags'] as $k => $t) {
  $fields[$k] = array(
    // description?
    'label' => $t['description'],
    'type'  => 'select',
    'options' => array(
      '' => 'netural',
      // com or mod?
      'com' => 'add to community queue',
      'mod' => 'add to moderator queue',
      '-' => 'remove any queueing',
    ),
  );
}

$formHtml = generateForm($params['action'], $fields, $values);

$portal = getBoardSettingsPortal($boardUri);

wrapContent($portal['header'] . 'Queueing Settings'. $formHtml . $portal['footer']);

?>
