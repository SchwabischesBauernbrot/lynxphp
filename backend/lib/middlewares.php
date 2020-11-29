<?php

// FIXME: refactor this out
// or make it simpler on the router/middleware...
// new name suggestion: boardExistsMiddleware ?
function boardMiddleware($request) {
  $boardUri = getQueryField('boardUri');
  $boardData = getBoardByUri($boardUri);
  if (!$boardData) {
    sendResponse(array(), 404, 'Board does not exist');
    return;
  }
  return $boardData;
}

// way more useful if we just pass boardUri in...
function boardOwnerMiddleware($request) {
  $boardUri = getQueryField('boardUri');
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  $ownedBoards = userBoards($user_id);
  $ok = false;
  // this will not have boardid
  foreach($ownedBoards as $board) {
    if ($board['uri'] === $boardUri) {
      $ok = true;
      break;
    }
  }
  if (!$ok) {
    wrapContent('You do not own this board');
    return;
  }
  return $boardUri;
}

?>
