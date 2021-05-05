<?php
// https://paragonie.com/book/pecl-libsodium/read/05-publickey-crypto.md
// https://github.com/paragonie/sodium_compat
include '../common/sodium/autoload.php';

function getEdKeypair($user, $pass) {
  // 16B + 16B = 32B binary
  $seed = md5($user, true) . md5($pass, true);

  // for signing
  $ed25519kp = \Sodium\crypto_sign_seed_keypair($seed); // sha512 32bytes of seed makes PK, seed becomes sk
  $edSkBin = \Sodium\crypto_sign_secretkey($ed25519kp); // get first 64 chars of param
  $edPkBin = \Sodium\crypto_sign_publickey($ed25519kp); // gets last 64 chars of param
  return array(
    'sk' => $edSkBin,
    'pk' => $edPkBin,
  );
}

// we can refactor down later
function getVerifiedChallengedSignature($user, $pass) {
  $eKp = getEdKeypair($user, $pass);

  $edSkBin = $eKp['sk'];
  $edPkBin = $eKp['pk'];

  // for encryption
  $xSkBin = \Sodium\crypto_sign_ed25519_sk_to_curve25519($edSkBin); //sha512 sk into x
  //$xPkBin = \Sodium\crypto_sign_ed25519_pk_to_curve25519($eKP['pk']); // does some curve math

  $res = getChallenge($edPkBin);
  if ($res === false) {
    return false;
  }
  //echo '<pre>getChal', print_r($res, 1), "</pre>\n";

  $edSrvPk = base64_decode($res['serverPubkey64']);
  $symKey = \Sodium\crypto_box_keypair_from_secretkey_and_publickey(
    $xSkBin,
    $edSrvPk
  ); // substrs

  $bytes = base64_decode($res['cipherText64']);
  $iv = substr($bytes, 0, \Sodium\CRYPTO_BOX_NONCEBYTES);
  $cipherText = substr($bytes, \Sodium\CRYPTO_BOX_NONCEBYTES);
  $chal = \Sodium\crypto_box_open(
    $cipherText,
    $iv,
    $symKey
  );

  // sign the challenge
  $sig = \Sodium\crypto_sign_detached($chal, $edSkBin);
  return array(
    'edPkBin' => $edPkBin,
    'chal' => $chal,
    'sig' => $sig,
  );
}

?>