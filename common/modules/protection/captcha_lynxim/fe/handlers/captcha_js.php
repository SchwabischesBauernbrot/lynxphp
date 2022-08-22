<?php

$params = $getHandler();

$text = captcha_generateCode();
$captcha = captcha_register($text);

// show image...
//header('Content-Type: image/jpeg');
$data = captcha_generateImage($text);
$data64 = base64_encode($data);

global $now;

header('Content-Type: application/json');
echo json_encode(array(
  'id' => $captcha['id'],
  'img' => $data64,
  'ex' => $captcha['expires'] - $now,
));
?>
