<?php

function getLoginForm($goto = false) {
  global $BASE_HREF;
  $templates = loadTemplates('login');
  if ($goto === false) $goto = '';
  $proto = DISABLE_HTTPS ? 'http' : 'https';
  $tags = array(
    'action' => $proto . '://' . BASE_HOST . $BASE_HREF . 'forms/login.php',
    'goto' => $goto,
    // FIXME get named route
    // we need a pipeline if forgot isn't base
    'forgot_account' => $BASE_HREF . 'forgot_account.html',
    'signup' => $BASE_HREF . 'signup.html',
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
  global $BASE_HREF;
  //echo "base[$BASE_HREF]<br>\n";
  redirectTo($BASE_HREF . 'forms/login.html');
}

function postLogin() {
  $user = $_POST['username'];
  $pass = $_POST['password'];

  if (AUTH_DIRECT) {
    //echo "user[$user]<br>\n";
    $res = backendLogin($user, $pass);
  } else {
    include '../frontend_lib/lib/lib.sodium.php';
    $resp = getVerifiedChallengedSignature($user, $pass);
    if ($resp === false) {
      /*
      // make sure first lines of output are see-able
      global $sentBump;
      echo '<div style="height: 40px;"></div>', "\n"; $sentBump = true;
      */
      wrapContent("Backend could not provide a challenge, please try again later");
      return;
    }
    $res = backendVerify($resp['chal'], $resp['sig'], $user, $pass);
  }
  //echo "<pre>postLogin", print_r($res, 1), "</pre>\n";
  if ($res === true) {
    //echo "goto[", $_POST['goto'], "]<br>\n";
    //redirectTo('/control_panel.php');
    //wrapContent('Login successful');
    global $BASE_HREF;
    if (!empty($_POST['goto'])) {
      // I wonder if we should filter this
      // only needed if we ever check HTTP_REFERRER
      // FIXME get named route
      redirectTo($BASE_HREF . $_POST['goto']);
    } else {
      redirectTo($BASE_HREF . 'control_panel.php');
    }
  } else {
    $tmpl = "Error: Log In incorrect or other error: " . print_r($res, 1) . "<br>\n";
    echo '<div style="height: 40px;"></div>', "\n";
    wrapContent($tmpl . getLoginForm());
  }
}

?>
