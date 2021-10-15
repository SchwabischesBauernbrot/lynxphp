<?php

// how is this better than lynx/account?
// more specific

$user_id = loggedIn();
if (!$user_id) {
  return;
}
$boards = userBoards($user_id);
sendResponse($boards);