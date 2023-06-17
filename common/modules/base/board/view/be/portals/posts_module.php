<?php

// be side as a module for sendResponse2
$params = $getModule();

// we own this
$io['out']['posts'] = array();

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
if (!$boardUri && isset($router->foundRoute['match']['params']['boardUri'])) {
  $boardUri = $router->foundRoute['match']['params']['boardUri'];
}
if (!$boardUri && isset($router->foundRoute['match']['params']['uri'])) {
  $boardUri = $router->foundRoute['match']['params']['uri'];
}

if (!$boardUri) {
  $io['out']['posts']['issue'] = 'no boardUri found';
  $io['out']['posts']['debug'] = $router->foundRoute;
}

$io['boardUri'] = $boardUri;

if ($boardUri) {
  // kind of a waste but we need to recover this...
  // since expectJson has no comms with useResource or router...
  $io['out']['posts']['uri'] = $boardUri;
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
$io['board'] = $boardSettings;

if (!empty($io['data']['posts'])) {
  $io['out']['posts']['threadPostCnt'] = count($io['data']['posts']);
  $files = 0;
  foreach($io['data']['posts'] as $p) {
    if (isset($p['files'])) {
      $files += count($p['files']);
    }
  }
  $io['out']['posts']['threadFileCnt'] = $files;
}

// board data? only if settings is missing
if (!is_bool($boardSettings)) {
  $io['out']['posts']['settings'] = $boardSettings;
}

/*
if (!empty($data['pageCount'])) {
  $out[$portal]['pageCount'] = $data['pageCount'];
}
if (!empty($data['pages'])) {
  $out[$portal]['pageCount'] = count($data['pages']);
}
*/
global $tpp;
//$boardData = getBoard($boardUri);
//echo "<pre>", print_r($boardData, 1), "</pre>\n";
if ($boardUri) {
  $posts_model = getPostsModel($boardUri);
  $tc = getBoardThreadCount($boardUri, $posts_model);
  $io['out']['posts']['pageCount'] = ceil($tc / $tpp);
}

// bandwidth saver
if (!count($io['out']['posts'])) {
  unset($io['out']['posts']);
}
?>