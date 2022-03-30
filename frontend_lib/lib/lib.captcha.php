<?php

function validate_captcha_field($options = false) {
  extract(ensureOptions(array(
    'field' => 'captcha',
  ), $options));

  $challenge = $_POST[$field];
  //echo "challenge[$challenge]<br>\n";
  if (empty($challenge)) {
    return 'CAPTCHA is required';
  }
  $captcha_id = $_POST['captcha_id'];
  if (empty($captcha_id)) {
    return 'CAPTCHA has no ID';
  }
  global $scratch, $now;
  $captchas = $scratch->get('captchas');
  if (!is_array($captchas)) {
    return'No CAPTCHAs active';
  }

  if (!isset($captchas[$captcha_id])) {
    return 'This CAPTCHA ID not found, please try again';
  }

  if ($captchas[$captcha_id]['expires'] < $now) {
    unset($captchas[$captcha_id]);
    $scratch->set('captchas', $captchas);
    return 'CAPTCHA expired, please try again';
  }

  if (strtolower($challenge) !== $captchas[$captcha_id]['value']) {
    return 'CAPTCHA is wrong, please try again';
  }

  // success, remove it
  unset($captchas[$captcha_id]);
  $scratch->set('captchas', $captchas);

  return '';
}

?>
