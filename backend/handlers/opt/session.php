<?php

$user_id = loggedIn();
if (!$user_id) {
  return;
}
sendResponse(array('session' => 'ok'));