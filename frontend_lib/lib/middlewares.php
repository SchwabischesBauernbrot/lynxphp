<?php

// FIXME: refactor this out
// or make it simpler on the router/middleware...

function boardOwnerMiddleware($request) {
  $boardUri = $request['params']['uri'];
  // do we own this board?
  $account = backendLynxAccount();
  $ok = false;

  // are we an admin or global?
  if (isset($account['groups'])) {
    if (in_array('admin', $account['groups']) || in_array('global', $account['groups'])) {
      $ok = true;
    }
  }

  if (isset($account['ownedBoards']) && is_array($account['ownedBoards'])) {
    foreach($account['ownedBoards'] as $board) {
      if ($board === $boardUri) {
        $ok = true;
        break;
      }
    }
  }
  // FIXME: board vols...
  if (!$ok) {
    wrapContent('You do not own this board');
    return;
  }
  return $boardUri;
}

?>
