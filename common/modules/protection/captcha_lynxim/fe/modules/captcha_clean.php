<?php

$params = $getModule();

// io is results but don't need it here

global $persist_scratch, $now;

$captchas = $persist_scratch->get('captchas');
if (!is_array($captchas)) $captchas = array();
if (DEV_MODE) {
  $startCount = count($captchas);
}
foreach($captchas as $captcha_id => $row) {
  if ($row['expires'] < $now) {
    unset($captchas[$captcha_id]);
  }
}
if (DEV_MODE) {
  $endCount = count($captchas);
  echo "$startCount => $endCount CAPTCHAS<br>\n";
}
$persist_scratch->set('captchas', $captchas);

?>