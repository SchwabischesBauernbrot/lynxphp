<?php

// user settings and lynx/account seem really similar...
$user_id = loggedIn();
if (!$user_id) {
  return;
}
sendResponse(array('session' => 'ok'));