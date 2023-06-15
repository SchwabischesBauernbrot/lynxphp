<?php

$params = $getModule();

// should this be enabled?
global $board_settings;
//echo "Settings [", gettype($board_settings), print_r($board_settings, 1),"]<br>\n";
if ($board_settings === false) {
  // get board settings...
  $boardData = getBoard($io['boardUri']);
  // don't commit with this
  if (isset($boardData['settings'])) {
    $board_settings = $boardData['settings'];
  }
}

if (isset($board_settings['captcha_mode'])) {
  $mode = $board_settings['captcha_mode']; // posts / thread / no
  $enable = false;
  if ($io['endpoint'] === 'lynx/newThread') {
    if ($mode !== 'No captcha' && $mode !== 'no') {
      $enable = true;
    }
  } else {
    // Reply
    if ($mode === 'posts') {
      $enable = true;
    }
  }
  //echo "mode[$mode] ep[", $io['endpoint'],"] enable[$enable]<br>\n";

  if ($enable) {
    $captchaErr = validate_captcha_field();
    if ($captchaErr) {
      $io['error'] = $captchaErr;
      //return?
    }
    /*
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
    */
  }
}

?>