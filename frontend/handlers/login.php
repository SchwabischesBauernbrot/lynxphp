<?php

function getLoginData() {
  $data = file_get_contents(BACKEND_BASE_URL . '4chan/boards.json');
  echo $data;
  $boards = array();
  return $boards;
}

function getLoginForm() {
  $secure_login_link = 'login.php';
  $secure_signup_link = 'signup.php';
  $content = <<< EOB
<form action="$secure_login_link" method="POST">
  <dl>
    <dt>
    <dd><input type=text name="username">
    <dt>
    <dd><input type=password name="password">
  </dl>
  <input type=submit value="log in">
</form>
<a href="$secure_signup_link">create account</a>
EOB;
  return $content;
}

function getLogin() {
  $boards  = getLoginData();
  wrapContent(getLoginForm());
}

function getLogout() {
  setcookie('session', '', 1, '/');
  wrapContent('You are now logged out');
}

function postLogin() {
  $login = backendLogin();
  if ($login === true) {
    redirectTo('control_panel.php');
  } else {
    $tmpl = "Error: Log In incorrect or other error<br>\n";
    wrapContent($tmpl . getLoginForm());
  }
}

?>
