<?php

function getSignupForm($goto = false) {
  $templates = loadTemplates('signup');
  if ($goto === false) $goto = '';

  // force TLS
  // FIXME get named route
  $secure_signup_link = 'https://' . BASE_HOST . BASE_HREF . 'signup';

  // set up form
  $formFields = array(
    'username' => array('type' => 'text', 'label' => 'Username'),
    'password' => array('type' => 'password', 'label' => 'Password (Minimum 16 chars, we recommend using a pass phrase)'),
    'email' => array('type' => 'email', 'label' => 'Recovery Email (Optional, we suggest using a burner/temp one)'),
  );
  // FIXME: pipeline
  $formOptions = array_merge(jsChanStyle(), array(
    'buttonLabel' => 'create account',
  ));
  // FIXME: pipeline
  $values = array();
  foreach($formFields as $f => $row) {
    $values[$f] = getOptionalPostField($f);
  }
  // FIXME: pipeline
  $tags = array(
    'form' => generateForm($secure_signup_link, $formFields, $values, $formOptions),
    'goto' => $goto,
    // FIXME get named route
    'forgot_account' => BASE_HREF . 'forgot_account',
    'login' => BASE_HREF . 'forms/login',
  );
  // FIXME: pipeline
  return replace_tags($templates['header'], $tags);
}

function getSignup() {
  $content = getSignupForm();
  wrapContent($content);
}

function postSignup() {
  $user  = $_POST['username'];
  $pass  = $_POST['password'];
  if (strlen($pass) < 16) {
    $tmpl = "Error: Sign up error: password must be at least 16 characters longs<br>\n";
    return wrapContent($tmpl . getSignupForm());
  }

  include 'lib/lib.sodium.php';
  $resp = getVerifiedChallengedSignature($user, $pass);
  if ($resp === false) {
    wrapContent("Backend could not provide a challenge, please try again later");
    return;
  }

  $email = getOptionalPostField('email');
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
}

?>