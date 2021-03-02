<?php

function getSignupForm() {
  $secure_login_link = 'login.php';
  $secure_signup_link = 'signup.php';
  $content = <<< EOB
<form action="$secure_signup_link" method="POST">
  <dl>
    <dt>Username
    <dd><input type=text name="username">
    <dt>Email (For forgot password, suggest using a burner/temp one)
    <dd><input type=email name="email">
    <dt>
    <dd><!-- input type=captcha name="captcha" -->
    <dt>Password
    <dd><input type=password name="password">
  </dl>
  <input type=submit value="create account">
</form>
<a href="$secure_login_link">log in</a>
EOB;
  return $content;
}

function getSignup() {
  $content = getSignupForm();
  wrapContent($content);
}

function postSignup() {
  $user  = $_POST['username'];
  $email = $_POST['email'];
  $pass  = $_POST['password'];
  // login, password, email
  $json = curlHelper(BACKEND_BASE_URL . 'lynx/registerAccount', array(
    'login'    => $user,
    'password' => $pass,
    'email'    => $email,
  ));
  //echo "json[$json]<br>\n";
  $result = expectJson($json, 'lynx/registerAccount');
  // echo "<pre>[", print_r($result, 1), "]",gettype($result),"</pre>\n";
  if (!empty($result['data']['id'])) {
    $result = backendLogin($user, $pass);
    if ($login === true) {
      redirectTo('control_panel.php');
      return;
    }
  }
  $tmpl = "Error: Sign up error: " . print_r($result['meta'], 1) . "<br>\n";
  wrapContent($tmpl . getSignupForm());
}

?>
