<?php

function getLoginForm() {
  $templates = loadTemplates('login');
  $tags = array(
    'action' => 'https://' . BASE_HOST . BASE_HREF . 'forms/login',
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
  wrapContent('You are now logged out');
}

function postLogin() {
  $login = backendLogin($_POST['username'], $_POST['password']);
  if ($login === true) {
    redirectTo('/control_panel.php');
  } else {
    $tmpl = "Error: Log In incorrect or other error<br>\n";
    wrapContent($tmpl . getLoginForm());
  }
}

?>
