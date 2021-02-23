<?php
$params = $get();

$userid = getUserID();
$settings = getUserSettings($userid);

sendResponse(array(
  'settings' => $settings['settings'],
  'loggedin' => $userid ? true : false,
  'session'  => $settings['session'],
));

?>