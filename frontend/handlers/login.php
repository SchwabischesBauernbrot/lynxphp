<?php

function getLoginData() {
  $data = file_get_contents(BACKEND_BASE_URL . '4chan/boards.json');
  echo $data;
  $boards = array();
  return $boards;
}

function getLogin() {
  $boards = getLoginData();
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
  wrapContent($content);
}

function postLogin() {
  $user  = $_POST['username'];
  $pass  = $_POST['password'];
  // login, password, email
  $data = curlHelper(BACKEND_BASE_URL . 'lynx/login', array(
    'login'    => $user,
    'password' => $pass,
  ), array('HTTP_X_FORWARDED_FOR' => getip()));
  //echo "data[$data]<br>\n";
  $res = json_decode($data, true);
  if ($res['data']['session']) {
    setcookie('session', $res['data']['session'], $res['data']['ttl'], '/');
    redirectTo('control_panel.php');
  } else {
    echo "Error<br>\n";
  }
}

?>
