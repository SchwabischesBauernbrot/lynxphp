<?php

$params = $getHandler();

$cid = $params['request']['params']['captcha_id'];
$_POST['captcha_id'] = $cid;

$err = validate_captcha_field(array('remove' => false));

header('Content-Type: application/json');
$res = array('ok' => $err === '' ? "ok" : "error");

if (DEV_MODE) {
  global $persist_scratch, $now;
  $captchas = $persist_scratch->get('captchas');
  $res['debug'] = array(
    'err' => $err,
    'captcha_id' => $cid,
    'captcha' => $_POST['captcha'],
    '_POST' => $_POST,
    'ourRecord' => $captchas[$cid],
    'captchas' => $captchas,
  );
}

echo json_encode($res);
?>
