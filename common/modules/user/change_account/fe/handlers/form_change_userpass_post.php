<?php

$params = $getHandler();

$user = $_POST['username'];
$pass = $_POST['password'];

$eKp = getEdKeypair($user, $pass);
$res = $pkg->useResource('migrate_account', array(
  'pk' => bin2hex($eKp['pk']),
));

if (!empty($res['data'])) {
  // FIXME get named route
  global $BASE_HREF;
  redirectTo($BASE_HREF . 'account?message=' . urlencode('Account migrated'));
} else {
  wrapContent('Error: ' . print_r($res) . getChangeUserPassForm());
}

?>