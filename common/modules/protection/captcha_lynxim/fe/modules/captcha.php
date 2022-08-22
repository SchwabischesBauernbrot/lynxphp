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

$text = captcha_generateCode();
$captcha = captcha_register($text);
$data = captcha_generateImage($text);
$data64 = base64_encode($data);

$io['html'] = '
<span class="col">
  <img src="data:image/jpeg;base64, '.$data64.'">
  <input type=hidden name="' . $io['field'] . '_id" value="' . $captcha['id'] . '">
  <input type=text maxlength=6 size=6 name="' . $io['field'] . '">
</span>
';

?>