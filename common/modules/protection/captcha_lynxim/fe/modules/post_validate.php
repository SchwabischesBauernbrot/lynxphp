<?php

$params = $getModule();

// should this be enabled?
$challenge = $_POST['captcha'];
//echo "challenge[$challenge]<br>\n";
if (empty($challenge)) {
  $io['error'] = 'CAPTCHA is required';
  return;
}
$captcha_id = $_POST['captcha_id'];
if (empty($captcha_id)) {
  $io['error'] = 'CAPTCHA has no ID';
  return;
}
global $scratch, $now;
$captchas = $scratch->get('captchas');
if (!is_array($captchas)) {
  $io['error'] = 'No CAPTCHAs active';
  return;
}

if (!isset($captchas[$captcha_id])) {
  $io['error'] = 'This CAPTCHA ID not found, please try again';
  return;
}

if ($captchas[$captcha_id]['expires'] < $now) {
  $io['error'] = 'CAPTCHA expired, please try again';
  unset($captchas[$captcha_id]);
  $scratch->set('captchas', $captchas);
  return;
}

if (strtolower($challenge) !== $captchas[$captcha_id]['value']) {
  $io['error'] = 'CAPTCHA is wrong, please try again';
  return;
}

// success, remove it
unset($captchas[$captcha_id]);
$scratch->set('captchas', $captchas);

?>