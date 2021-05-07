<?php

$params = $get();

$edPkBin = verifyChallengedSignatureHandler();
if (!$edPkBin) {
  return;
}
global $db, $models;

$res = $db->find($models['user'], array('criteria' => array(
  array('publickey', '=', bin2hex($edPkBin)),
)));
if ($db->num_rows($res)) {
  // FIXME: should we just log in, they proved their key...
  return sendResponse(array(), 403, 'Already registered');
}

//echo "Creating<br>\n";
$row = array('publickey' => bin2hex($edPkBin));
$em = getOptionalPostField('email');
if ($em) $row['email'] = hash('sha512', BACKEND_KEY . $em . BACKEND_KEY);
$id = $db->insert($models['user'], array($row));
loginResponseMaker($id);

?>