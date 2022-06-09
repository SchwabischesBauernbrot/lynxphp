<?php
$params = $get();

global $db, $models;

$action = $_POST['action'];
$rows = array();
$posts = json_decode($_POST['strings'], true);
if (is_array($posts)) {
  foreach($posts as $s) {
    $rows[] = array('string' => trim($s), 'action' => $action);
  }
}

sendResponse(array(
  'count' => count($rows),
  'post' => $_POST,
  'success' => count($rows) ? $db->insert($models['post_string'], $rows) : false,
));

?>
