<?php

$module = $getModule();

if (in_array('board', $io['portals'])) {
  // need to access request but all we got is response
  // and it seems board isn't always set here...
  if (isset($io['data']['board'])) {
    $boardUri = $io['data']['board']['uri'];
  } else
  if (isset($io['data']['uri'])) {
    $boardUri = $io['data']['uri'];
  }
  //echo "boardUri[$boardUri]<br>\n";

  // get all moderation actions for this user on this board
  $ip = getip(); // ::1
  //echo "ip[$ip]<br>\n";
  $id = getIdentity();
  //echo "id[$id]<br>\n";

  // if no session, we can't cancel out the votes
  if ($id === 'session_' || $ip === '::1' || $ip === '127.0.0.1') {
    // and we should just show no number for now
    return;
  }

  if (!isset($io['out']['boards'])) $io['out']['boards'] = array();
  $queue = getYourQueue($boardUri);
  $io['out']['board']['post_queueing'] = array(
    'count' => count($queue),
  );
}

?>