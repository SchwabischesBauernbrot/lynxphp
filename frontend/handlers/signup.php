<?php

function getSignup() {
  $secure_login_link = 'login.php';
  $secure_signup_link = 'signup.php';
  $content = <<< EOB
<form action="$secure_signup_link" method="POST">
  <dl>
    <dt>Username
    <dd><input type=text name="username">
    <dt>Email
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
  wrapContent($content);
}

function postSignup() {
  $user  = $_POST['username'];
  $email = $_POST['email'];
  $pass  = $_POST['password'];
  // login, password, email
  $data = curlHelper(BACKEND_BASE_URL . 'lynx/registerAccount', array(
    'login'    => $user,
    'password' => $pass,
    'email'    => $email,
  ));
  echo $data;
}

?>
