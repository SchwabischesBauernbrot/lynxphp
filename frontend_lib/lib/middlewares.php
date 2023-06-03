<?php

// FIXME: refactor this out
// or make it simpler on the router/middleware...

function boardOwnerMiddleware($request) {
  $boardUri = $request['params']['uri'];
  $ok = false;
  // FIXME: board vols...

  // probably faster to let the backend check...
  // then doing a potential call here
  // fine on when not BO but if you are it's two BE calls
  if (perms_isBO($boardUri) || perms_inGroups(array('admin', 'global'))) {
    $ok = true;
  }
  /*
  // do we own this board?
  $account = backendLynxAccount();

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
  */
  if (!$ok) {
    wrapContent('You do not own this board');
    return;
  }
  return $boardUri;
}

?>
