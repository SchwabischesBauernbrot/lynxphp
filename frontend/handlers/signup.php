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
  $result = curlHelper(BACKEND_BASE_URL . 'lynx/registerAccount', array(
    'login'    => $user,
    'password' => $pass,
    'email'    => $email,
  ));
  //echo $data;
  if (!empty($result['data']['username'])) {
    $result = backendLogin($user, $pass);
    if ($login === true) {
      redirectTo('control_panel.php');
      return;
    }
  }
  $tmpl = "Error: Sign up error: " . $result['meta']['err'] . "<br>\n";
  wrapContent($tmpl . getSignupForm());
}

?>
