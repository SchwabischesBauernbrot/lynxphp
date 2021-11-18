<?php

$params = $getModule();

// io is formFields

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
  if ($io['type'] === 'Thread') {
    if ($mode !== 'no') {
      $enable = true;
    }
  } else {
    // Reply
    if ($mode === 'posts') {
      $enable = true;
    }
  }
  if ($enable) {
    $io['formfields']['captcha'] = array( 'type' => 'captcha', 'label' => 'Captcha');
  }
}



?>