<?php
$params = $get();

$uri = $params['params']['boardUri'];

/*
$boardData = boardMiddleware($request);
if (!$boardData) {
  return sendResponse(array());
}
*/

// ensure browse will have a session
$setCookie = NULL;
$userid = getUserID(); // are we logged in?
$sesRow = ensureSession($userid); // sends a respsone on 500
if (!$sesRow) {
  return; // 500
}
// did we just make it?
global $now;
if (isset($sesRow['created']) && (int)$sesRow['created'] === (int)$now) {
  // not going to have a username to send
  $setCookie = array(
    'name'  => 'session',
    'value' => $sesRow['session'],
    'ttl'   => $sesRow['expires'],
  );
}

// FIXME: check board settings...

global $db, $models, $tpp;

$res = $db->find($models['board_react'], array('criteria' => array(
  array('board_uri', '=', $uri),
)));
$reacts = $db->toArray($res);

// FIXME: include board settings...

//sendResponse($banners, 200, '', array('board' => $boardData));
sendResponse2($reacts, array(
  'meta' => array('setCookie' => $setCookie,)
));

?>
