<?php

// does this user have this perms
// on optional object?

$user_id = loggedIn();
if (!$user_id) {
  // well if you anon you don't get EXTRA permissions
  return; // already sends something...
}
// where is isUserPermitted located? interface/users
$access = isUserPermitted($user_id, $request['target'], $request['params']['perm']);
sendResponse(array(
  'access' => $access,
  'user_id' => $user_id,
));