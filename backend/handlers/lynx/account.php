<?php

$user_id = loggedIn();
if (!$user_id) {
  return;
}
//echo "user_id[$user_id]<br>\n";
$userRes = getAccount($user_id);
if (!$userRes) {
  // sendJson for consistency
  return sendJson(array('err' => 'user_id has been deleted'), array('code' => 400));
  //return sendResponse2(array(), array('code' => 400, 'err' => 'user_id has been deleted'));
}
$ownedBoards = userBoards($user_id);
$groups = getUserGroups($user_id);
$isAdmin  = userInGroup($user_id, 'admin');
$isGlobal = userInGroup($user_id, 'global');

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

global $pipelines;
$io = array(
  'userid' => $user_id,
  'account' => $account,
);
// roles uses this
$pipelines[PIPELINE_ACCOUNT_DATA]->execute($io);

sendJson($io['account']);
