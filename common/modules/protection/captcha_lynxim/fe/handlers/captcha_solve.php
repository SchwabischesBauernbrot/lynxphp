<?php

$params = $getHandler();

$cid = $params['request']['params']['captcha_id'];
$_POST['captcha_id'] = $cid;

$err = validate_captcha_field(array('remove' => false));

header('Content-Type: application/json');
echo json_encode(array(
  'ok' => $err === '',
));
?>
