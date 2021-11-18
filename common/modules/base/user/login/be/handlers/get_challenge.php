<?php

$params = $get();

if (!hasPostVars(array('i'))) {
  // hasPostVars already outputs
  return;
}
include '../common/sodium/autoload.php';

// so you claim to have this identity, prove it
$edSrvKp = \Sodium\crypto_box_keypair();
$edSrvSk = \Sodium\crypto_box_secretkey($edSrvKp);
$edSrvPk = \Sodium\crypto_box_publickey($edSrvKp);
$token =  md5(uniqid());
$destEdPk = base64_decode($_POST['i']); // edPk stored as b64
$destXPkBin = \Sodium\crypto_sign_ed25519_pk_to_curve25519($destEdPk);

$symKey = \Sodium\crypto_box_keypair_from_secretkey_and_publickey(
  $edSrvSk,
  $destXPkBin
);
$iv = \Sodium\randombytes_buf(\Sodium\CRYPTO_BOX_NONCEBYTES);
$cipherText = \Sodium\crypto_box($token, $iv, $symKey);
global $db, $models, $now;
$db->insert($models['auth_challenge'], array(array(
  'challenge' => $token,
  'publickey' => $_POST['i'], // edPk stored as b64
  'expires'   => (int)$now,
  'ip'        => getip(),
)));

// also could send a server public key to encrypt the verify payload
// but the TLS transport should take care of that if needed
// generate id
$data = array(
  'cipherText64' => base64_encode($iv . $cipherText),
  'serverPubkey64' => base64_encode($edSrvPk),
);
// storage it temporarily w/expiration
// return it
sendResponse($data);
?>