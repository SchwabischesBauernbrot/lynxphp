<?php

$params = $get();

// verify if BO
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return;
}
$tno = $params['params']['tno'];

//scrubThead($boardUri, $tno);
deleteThread($boardUri, $tno);

sendResponse2(array(
  'status' => 'ok',
  'debug' => array(
    'tno' => $tno,
  )
));