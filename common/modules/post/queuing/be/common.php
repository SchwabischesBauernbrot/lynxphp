<?php

// shared code for the backend

function getIdentity() {
  $userid = getUserID(); // are we logged in?
  if ($userid) return 'user_' .  $userid;
  $sid = getServerField('HTTP_SID');
  // does it matter if it's valid or expired? no it doesn't
  return 'session_' . $sid;
}

?>