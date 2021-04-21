<?php

function getLoginForm($goto = false) {
  $templates = loadTemplates('login');
  if ($goto === false) $goto = '';
  $tags = array(
    // force TLS
    'action' => 'https://' . BASE_HOST . BASE_HREF . 'forms/login',
    'goto' => $goto,
    // FIXME get named route
    'forgot_account' => BASE_HREF . 'forgot_account',
    'signup' => BASE_HREF . 'signup',
  );
  return replace_tags($templates['header'], $tags);
}

function getLogin() {
  // FIXME: are you logged in or not?
  // if logged in, display log out link instead
  wrapContent(getLoginForm());
}

function getLogout() {
  setcookie('session', '', 1, '/');
  //wrapContent('You are now logged out');
  redirectTo(BASE_HREF . 'forms/login');
}

function postLogin() {
  $user = $_POST['username'];
  $pass = $_POST['password'];

  include 'lib/lib.sodium.php';
  $resp = getVerifiedChallengedSignature($user, $pass);
  if ($resp === false) {
    wrapContent("Backend could not provide a challenge, please try again later");
    return;
  }
  $res = backendVerify($resp['chal'], $resp['sig'], $user, $pass);
  //echo "<pre>postLogin", print_r($res, 1), "</pre>\n";
  if ($res === true) {
    //echo "goto[", $_POST['goto'], "]<br>\n";
    //redirectTo('/control_panel.php');
    //wrapContent('Login successful');
    if (!empty($_POST['goto'])) {
      // I wonder if we should filter this
      // only needed if we ever check HTTP_REFERRER
      // FIXME get named route
      redirectTo(BASE_HREF . $_POST['goto']);
    } else {
      redirectTo(BASE_HREF . 'control_panel');
    }
  } else {
    $tmpl = "Error: Log In incorrect or other error: " . print_r($res, 1) . "<br>\n";
    wrapContent($tmpl . getLoginForm());
  }
}

?>
