<?php

// by loggedIn already outputting on failure
// that means we need to output on all failure cases...

// FIXME: refactor this out
// or make it simpler on the router/middleware...
// new name suggestion: boardExistsMiddleware ?
function boardMiddleware($request, $options = false) {
  $boardUri = getQueryField('boardUri');
  // also accept uri in request.params
  if (!$boardUri && isset($request['params']['uri'])) {
    $boardUri = $request['params']['uri'];
  }
  if (!$boardUri && isset($request['params']['boardUri'])) {
    $boardUri = $request['params']['boardUri'];
  }
  if (!$boardUri) {
    sendResponse2(array(), array('code' => 400, 'err' => 'No boardUri passed'));
    return;
  }
  extract(ensureOptions(array(
    'getPageCount' => false,
    'include_fields' => array('settings'),
  ), $options));
  $boardData = getBoardByUri($boardUri, array('include_fields' => $include_fields));
  if (!$boardData) {
    sendResponse2(array(), array('code' => 404, 'err' => 'Board does not exist'));
    return;
  }

  if ($getPageCount) {
    $posts_model = getPostsModel($boardUri);
    $threadCount = getBoardThreadCount($boardUri, $posts_model);
    global $tpp;
    $boardData['pageCount'] = ceil($threadCount / $tpp);
  }
  return $boardData;
}

// way more useful if we just pass boardUri in...
// FIXME: bring on par with boardMiddleware
// but we don't always need that data
// so options?
function boardOwnerMiddleware($request) {
  $boardUri = getQueryField('boardUri');
  // also accept uri in request.params
  if (!$boardUri && isset($request['params']['uri'])) {
    $boardUri = $request['params']['uri'];
  }
  if (!$boardUri && isset($request['params']['boardUri'])) {
    $boardUri = $request['params']['boardUri'];
  }
  if (!$boardUri) {
    sendResponse2(array(), array('code' => 400, 'err' => 'No boardUri passed'));
    return;
  }
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  $ownedBoards = userBoards($user_id);

  $ok = isUserPermitted($user_id, 'b/' . $boardUri);

  if (!$ok) {
    // this will not have boardid
    foreach($ownedBoards as $board) {
      if ($board === $boardUri) {
        $ok = true;
        break;
      }
    }
  }
  if (!$ok) {
    //wrapContent('You do not own this board: [' . $boardUri . ']' . print_r($ownedBoards, 1));
    sendResponse2(array(), array('code' => 400, 'err' => 'You do not own this board'));
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
    sendResponse2(array(), array('code' => 401, 'err' => 'One of these access groups is required: '. join(',', $groups)));
    return;
  }
  return true;
}

?>
