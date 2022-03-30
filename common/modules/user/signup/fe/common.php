<?php

// we could more lib.sodium in here
// if we move login in this module too
// but then we can't disable the signups easily
// and login really shoudl be base and not disabled...
// lib.sodium stays in frontend for login

function getSignupForm($goto = false) {
  $templates = loadTemplates('signup');
  if ($goto === false) $goto = '';

  // force TLS
  // FIXME get named route
  $secure_signup_link = 'https://' . BASE_HOST . BASE_HREF . 'signup.php';

  // set up form
  $formFields = array(
    'username' => array('type' => 'text', 'label' => 'Username'),
    'password' => array('type' => 'password', 'label' => 'Password (Minimum 16 chars, we recommend using a pass phrase)'),
    'email' => array('type' => 'email', 'label' => 'Recovery Email (Optional, we suggest using a burner/temp one)'),
    'captcha' => array( 'type' => 'captcha', 'label' => 'Captcha'),
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
    'forgot_account' => BASE_HREF . 'forgot_account.html',
    'login' => BASE_HREF . 'forms/login.html',
  );
  // FIXME: pipeline
  return replace_tags($templates['header'], $tags);
}

return array();

?>
