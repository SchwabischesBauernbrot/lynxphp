<?php

// be side as a module for sendResponse2
$params = $getModule();

$io['out']['boardSettings'] = array();

// so what you header need?
// input: we need to know which board (uri) context
// should it be passed? in the qs?
$boardUri = getQueryField('boardUri');
if (!$boardUri && isset($io['data']['uri'])) {
  $boardUri = $io['data']['uri'];
}
// roles
if (!$boardUri && isset($io['data']['board_uri'])) {
  $boardUri = $io['data']['board_uri'];
}
// queueing
if (!$boardUri && isset($io['meta']['boardUri'])) {
  $boardUri = $io['meta']['boardUri'];
}

global $router;
if (!$boardUri && $router->foundRoute['match']['params']['boardUri']) {
  $boardUri = $router->foundRoute['match']['params']['boardUri'];
}
if (!$boardUri && $router->foundRoute['match']['params']['uri']) {
  $boardUri = $router->foundRoute['match']['params']['uri'];
}

if (!$boardUri) {
  $io['out']['boardSettings']['issue'] = 'no boardUri found';
  $io['out']['boardSettings']['debug'] = $router->foundRoute;
}

$io['boardUri'] = $boardUri;

if ($boardUri) {
  // kind of a waste but we need to recover this...
  // since expectJson has no comms with useResource or router...
  $io['out']['boardSettings']['uri'] = $boardUri;
}

// well we assume their logged in
// board settings
// - settings_queueing_mode is example of what it needs
// - maxMessageLength
$boardSettings = false;

// this type of extraction should be done on the frontend side
// but we need to know if it's there or not
if (isset($io['data']['settings'])) {
  $boardSettings = true;
}
if (isset($io['data']['boardSettings'])) {
  $boardSettings = true;
}
if (!$boardSettings && isset($io['meta']['board']['settings'])) {
  $boardSettings = true;
}

if (!$boardSettings) {
  $board = getBoard($boardUri, array('jsonFields' => array('settings')));
  if ($board) {
    $boardSettings = $board['settings'];
  }
}
$io['boardSettings'] = $boardSettings;

// board data? only if settings is missing
if (!is_bool($boardSettings)) {
  $io['out']['boardSettings']['settings'] = $boardSettings;
}

// bandwidth saver
if (!count($io['out']['boardSettings'])) {
  unset($io['out']['boardSettings']);
}
?>