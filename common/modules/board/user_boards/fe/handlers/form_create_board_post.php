<?php

$params = $getHandler();

/*
$res = $pkg->useResource('change_email', array(
  'em' => $_POST['email'],
));

if (!empty($res['data'])) {
  // FIXME get named route
  global $BASE_HREF;
  redirectTo($BASE_HREF . 'account?message=' . urlencode('Recovery email changed'));
} else {
  wrapContent('Error: ' . print_r($res) .  getChangeEmailForm());
}
*/

$captchaErr = validate_captcha_field();
if ($captchaErr) {
  $tmpl = "Error: Board creation error: CAPTCHA error: $captchaErr<br>\n";
  return wrapContent($tmpl . getCreateBoardForm());
}

// FIXME:
$result = backendCreateBoard();
if ($result['data'] === 'ok') {
  // maybe not display this?
  //wrapContent('Board created!');
  // FIXME get named route
  global $BASE_HREF;
  redirectTo($BASE_HREF . 'control_panel.php');
  /*
  $uri = $_POST['uri'];
  redirectTo($uri . '/settings');
  */
  return;
}
$tmpl = "Error: Board creation error: " . $result['meta']['err'] . "<br>\n";
wrapContent($tmpl . getCreateBoardForm());


?>