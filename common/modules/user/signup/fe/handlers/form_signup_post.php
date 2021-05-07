<?php

$params = $getHandler();

$user  = $_POST['username'];
$pass  = $_POST['password'];
if (strlen($pass) < 16) {
  $tmpl = "Error: Sign up error: password must be at least 16 characters longs<br>\n";
  return wrapContent($tmpl . getSignupForm());
}

include '../frontend_lib/lib/lib.sodium.php';
$resp = getVerifiedChallengedSignature($user, $pass);
if ($resp === false) {
  wrapContent("Backend could not provide a challenge, please try again later");
  return;
}

$email = getOptionalPostField('email');
// FIXME: switch to useResource
$res = backendRegister($resp['chal'], $resp['sig'], $email);

if ($res === true) {
  //wrapContent('Account registered, and logged in!');
  if (!empty($_POST['goto'])) {
    // I wonder if we should filter this
    // only needed if we ever check HTTP_REFERRER
    redirectTo(BASE_HREF . $_POST['goto']);
  } else {
    redirectTo(BASE_HREF . 'control_panel');
  }
  return;
}

/*
// echo "<pre>[", print_r($result, 1), "]",gettype($result),"</pre>\n";
if (!empty($result['data']['id'])) {
  $result = backendLogin($user, $pass);
  //echo "<pre>[", print_r($result, 1), "]", gettype($result),"</pre>\n";
  if ($result === true) {
    redirectTo('control_panel.php');
    return;
  }
}
*/
$tmpl = "Error: Sign up error: " . print_r($res, 1) . "<br>\n";
wrapContent($tmpl . getSignupForm());

?>