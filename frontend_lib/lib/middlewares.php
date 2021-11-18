<?php

// FIXME: refactor this out
// or make it simpler on the router/middleware...

function boardOwnerMiddleware($request) {
  $boardUri = $request['params']['uri'];
  // do we own this board?
  $account = backendLynxAccount();
  $ok = false;
  if (isset($account['ownedBoards']) && is_array($account['ownedBoards'])) {
    foreach($account['ownedBoards'] as $board) {
      if ($board === $boardUri) {
        $ok = true;
        break;
      }
    }
  }
  if (!$ok) {
    wrapContent('You do not own this board');
    return;
  }
  return $boardUri;
}

?>
