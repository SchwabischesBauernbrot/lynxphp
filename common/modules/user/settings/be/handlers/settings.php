<?php
$params = $get();

$userid = getUserID();
$userRes = getAccount($userid);
if (!$userRes) {
  return sendJson(array('err' => 'user_id has been deleted'), array('code' => 400));
}

$settings = getUserSettings($userid);

$ownedBoards = userBoards($user_id);
$groups = getUserGroups($user_id);
$isAdmin  = userInGroup($user_id, 'admin');
$isGlobal = userInGroup($user_id, 'global');

/*
$account = array(
  'noCaptchaBan' => false,
  'login' => empty($userRes['username']) ? $userRes['publickey'] : $userRes['username'],
  'email' => $userRes['email'],
  'globalRole' => $isAdmin ? 1 : ($isGlobal ? 2 : 99),
  //'disabledLatestPostings'
  //'volunteeredBoards'
  'boardCreationAllowed' => true,
  'ownedBoards' => $ownedBoards,
  'groups' => $groups,
  //'settings'
  'reportFilter' => array(), // category filters for e-mail notifications
  // outside spec
  'username' => $userRes['username'],
  'publickey' => $userRes['publickey'],
);
*/

// seems to be mainly or lib.fe.settings.php
// maybe it was more stripped down
// only needed account.publickey, ownedBoards, and groups
sendResponse(array(
  'settings' => $settings['settings'],
  'loggedIn' => $userid ? true : false,
  'session'  => $settings['session'],
  // username / pubkey
  'account' => array(
    'login' => empty($userRes['username']) ? $userRes['publickey'] : $userRes['username'],
    'email' => $userRes['email'],
    'globalRole' => $isAdmin ? 1 : ($isGlobal ? 2 : 99),  
    'publickey' => $userRes['publickey'],
    'username' => $userRes['username'],
    'ownedBoards' => $ownedBoards,
    'groups' => $groups,
  ),
  // why are these down here?
  // lynx/account (also has settings)
  //'globalRole' => $isAdmin ? 1 : ($isGlobal ? 2 : 99),
  //'ownedBoards' => $ownedBoards,
  //'groups' => $groups,
  // login / email
));

?>