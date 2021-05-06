<?php
$params = $get();

$userid = getUserID();
$settings = getUserSettings($userid);

$isAdmin = $userid ? userInGroup($user_id, array('admin')) : false;
if (!$isAdmin) {
  return sendResponse(array(
    'site' => getPublicSiteSettings(),
    'user' => $settings['settings'],
  ));
}

sendResponse(array(
  'site' => getAllSiteSettings(),
  'user' => $settings['settings'],
));
?>