<?php
$params = $get();

$pass = getUserID() ? userInGroup($user_id, array('admin')) : false;
if (!$pass) {
  return sendResponse(getPublicSiteSettings());
}

sendResponse(getAllSiteSettings());
?>