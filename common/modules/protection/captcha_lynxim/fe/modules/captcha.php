<?php

$params = $getModule();

// io is:
$details = $io['details'];

// generate and store challenge on the backend...
// generate captcha image
// send image and possible a session ID...
// can't send an SID because they could copy it...

// well we can ping the backend for permanent storage
// give me a code and reference? and expiration?
// no reason for the storage to be on the backend
// best we have frontend cache storage

$level = 1;
$width = 286;
$height = 100;
global $scratch;
$imageFont = 'AvantGarde-Book';  // convert -list font

$text = '';
for($i = 0; $i < 6; $i++) {
  $text .= chr(rand(0, 100) > 50 ? rand(97, 97 + 6) : rand(48, 57));
}

global $scratch, $now;
$captcha_id = md5(uniqid());
$captchas = $scratch->get('captchas');
if (!is_array($captchas)) $captchas = array();
$captchas[$captcha_id] = array(
  'id' => $captcha_id,
  'value' => $text,
  'session' => isset($_COOKIE['session']) ? $_COOKIE['session'] : '',
  'ip' => getip(),
  // but needs to be validate for an entire page load...
  // for no js...
  // good for 24 hours
  'expires' => (int)($now) + (24 * 60 * 60),
);
$scratch->set('captchas', $captchas);

$lineOffset = rand(-20, 20) / $level;
$command = '';
for($i = 0; $i < 5 * $level; $i++) {
  $lineWidth = rand(10, 20) / $level;
  if ($i) {
    $command .= ' ';
  }
  $command .= 'rectangle 0,' . $lineOffset . ' ' . $width . ',';
  $command .= $lineWidth + $lineOffset;
  $lineOffset += rand(20, 30) / $level;
  $lineOffset += $lineWidth;
}
//$command .= '\" -write mpr:mask +delete';


$cli = '/usr/bin/convert -size ' . $width . 'x' . $height . ' xc: -draw "' . $command . '" -write mpr:mask +delete';
$cli .= ' xc: -pointsize 70 -gravity center -font ' . $imageFont;
$cli .= ' -draw "text 0,0 \'' . $text . '\'" -write mpr:original +delete';
$cli .= ' mpr:original -negate -write mpr:negated +delete';
$cli .= ' mpr:negated mpr:original mpr:mask -composite';
$cli .= ' ' . distortImage($width, $height) . '-blur 0x1 jpg:-';

$data = `$cli`;
$data64 = base64_encode($data);

$io['html'] = '
<span class="col">
  <img src="data:image/jpeg;base64, '.$data64.'">
  <input type=hidden name="' . $io['field'] . '_id" value="' . $captcha_id . '">
  <input type=text maxlength=6 size=6 name="' . $io['field'] . '">
</span>
';

?>