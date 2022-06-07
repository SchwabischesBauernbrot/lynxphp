<?php
$params = $get();

global $db, $models;
$res = $db->find($models['post_string']);
$strings = $db->toArray($res);

// send values
sendResponse2(array(
  'strings' => $strings
));

?>