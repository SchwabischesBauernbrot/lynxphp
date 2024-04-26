<?php

$params = $get();

// verify if BO
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return;
}
$pno = $params['params']['pno'];

scrubPost($boardUri, $pno);

sendResponse2(array(
  'status' => 'ok',
  'debug' => array(
    'pno' => $pno,
  )
));