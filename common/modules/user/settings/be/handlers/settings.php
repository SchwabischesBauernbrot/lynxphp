<?php
$params = $get();

$userid = getUserID();
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

sendResponse(array(
  'settings' => $settings['settings'],
  'loggedIn' => $userid ? true : false,
  'session'  => $settings['session'],
  // username / pubkey
  // lynx/account (also has settings)
  'globalRole' => $isAdmin ? 1 : ($isGlobal ? 2 : 99),
  'ownedBoards' => $ownedBoards,
  'groups' => $groups,
  // login / email
));

?>