<?php
$params = $get();

// could be keyed by our user record in settings
$settings = getUserSettings();
// scope it down
$resp = array(
  'settings' => $settings['settings'],
  'loggedIn' => $settings['loggedin'],
  'session'  => $settings['session'],
);

if ($settings['loggedin']) {
  // username / pubkey
  // why are these down here?
  // lynx/account (also has settings)
  //'globalRole' => $isAdmin ? 1 : ($isGlobal ? 2 : 99),
  //'ownedBoards' => $ownedBoards,
  //'groups' => $groups,
  // login / email
  $userid = $settings['userid'];
  $userRes = getAccount($userid);
  $ownedBoards = userBoards($user_id);
  $groups = getUserGroups($user_id);
  $isAdmin  = userInGroup($user_id, 'admin');
  $isGlobal = userInGroup($user_id, 'global');

  // seems to be mainly or lib.fe.settings.php
  // maybe it was more stripped down
  // only needed account.publickey, ownedBoards, and groups
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
  $resp['account'] = array(
    'login' => empty($userRes['username']) ? $userRes['publickey'] : $userRes['username'],
    'email' => $userRes['email'],
    'globalRole' => $isAdmin ? 1 : ($isGlobal ? 2 : 99),
    'publickey' => $userRes['publickey'],
    'username' => $userRes['username'],
    'ownedBoards' => $ownedBoards,
    'groups' => $groups,
  );
}

sendResponse($resp);

?>