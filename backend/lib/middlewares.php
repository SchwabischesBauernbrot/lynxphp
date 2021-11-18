<?php

// by loggedIn already outputting on failure
// that means we need to output on all failure cases...

// FIXME: refactor this out
// or make it simpler on the router/middleware...
// new name suggestion: boardExistsMiddleware ?
function boardMiddleware($request) {
  $boardUri = getQueryField('boardUri');
  if (!$boardUri) {
    sendResponse(array(), 400, 'No boardUri passed');
    return;
  }
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
  if (!$boardUri) {
    sendResponse(array(), 400, 'No boardUri passed');
    return;
  }
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  $ownedBoards = userBoards($user_id);
  $ok = false;
  // this will not have boardid
  foreach($ownedBoards as $board) {
    if ($board === $boardUri) {
      $ok = true;
      break;
    }
  }
  if (!$ok) {
    //wrapContent('You do not own this board: [' . $boardUri . ']' . print_r($ownedBoards, 1));
    sendResponse(array(), 400, 'You do not own this board');
    return;
  }
  return $boardUri;
}

function userInGroupMiddleware($request, $groups) {
  $user_id = loggedIn();
  if (!$user_id) {
    // loggedIn will send something
    return;
  }
  $pass = userInGroup($user_id, $groups); // does not send something
  if (!$pass) {
    sendResponse(array(), 401, 'One of these access groups is required: '. join(',', $groups));
    return;
  }
  return true;
}

?>
