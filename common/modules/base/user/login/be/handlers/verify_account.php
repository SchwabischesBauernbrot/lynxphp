<?php

$params = $get();

// should only work over TLS unless same ip/localhost
$edPkBin = verifyChallengedSignatureHandler();
if (!$edPkBin) {
  return;
}
global $db, $models;

// process account upgrades, remove code later
$upgradedAccount = false;
if (1) {
  $u = strtolower(getOptionalPostField('u'));
  //echo "u[$u]<br>\n";
  if ($u && isset($_POST['p'])) {
    $p = $_POST['p'];
    //echo "Trying to locate user [$u]<br>\n";
    $res = $db->find($models['user'], array('criteria' => array(
      array('username', '=', $u),
    )));
    $row = $db->get_row($res);
    $db->free($res);
    //echo "id[", $row['userid'], "] pk[", $row['publickey'], "] p[$p]<br>\n";
    if ($row && $row['userid'] && !$row['publickey'] && password_verify($p, $row['password']) && strpos($row['email'], '@') !== false) {
      // convert users - ONLY DO THIS ONCE
      // Should we clear out the email?
      $db->updateById($models['user'], $row['userid'], array(
        'username' => '', 'password' => '', 'publickey' => bin2hex($edPkBin),
        'email' => hash('sha512', BACKEND_KEY . $row['email'] . BACKEND_KEY)));
      $upgradedAccount = true;
    }
  }
}

$res = $db->find($models['user'], array('criteria' => array(
  array('publickey', '=', bin2hex($edPkBin)),
)));
if (!$db->num_rows($res)) {
  $db->free($res);
  return sendResponse(array(), 401, 'Incorrect login - key is not registered, please sign up');
}
$row = $db->get_row($res);
$db->free($res);
$id = $row['userid'];
loginResponseMaker($id, $upgradedAccount);

?>