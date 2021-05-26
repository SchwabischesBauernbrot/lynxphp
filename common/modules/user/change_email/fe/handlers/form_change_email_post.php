<?php

$params = $getHandler();

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

?>