<?php

// fe side (for lib.backend's expectJson)
$params = $getModule();


// call this function after the portal call
// maybe this is a module call
//echo "<pre>", print_r($io, 1), "</pre>\n";

$meta = $io['resp']['meta'];
$data = $io['resp']['data'];

$boardUri = empty($io['portalData']['uri']) ? '' : $io['portalData']['uri'];
/*
if (!$boardUri && isset($data['uri'])) {
  $boardUri = $data['uri'];
}
// users
if (!$boardUri && isset($data[0]['uri'])) {
  $boardUri = $data[0]['uri'];
}
// roles
if (!$boardUri && isset($data[0]['board_uri'])) {
  $boardUri = $data[0]['board_uri'];
}
// queue
if (!$boardUri && isset($meta['board']['uri'])) {
  $boardUri = $meta['board']['uri'];
}
// have no uri in response: reacts/reports/flood
*/

$boardSettings = false;
if (isset($data['settings'])) {
  $boardSettings = $data['settings'];
}
if (!$boardSettings && isset($data['boardSettings'])) {
  $boardSettings = $data['boardSettings'];
}
if (!$boardSettings && isset($meta['board']['settings'])) {
  $boardSettings = $meta['board']['settings'];
}
if (!$boardSettings && isset($io['portalData']['settings'])) {
  $boardSettings = $io['portalData']['settings'];
}

//echo "uri[$boardUri]<br>\n";
//echo "<pre>found", print_r($boardSettings, 1), "</pre>\n";

// we can extract from data
// but what's the standard of how we communicate things?
// returning them?

// upload so all other functions can access
global $boards_settings;
$boards_settings[$boardUri] = $boardSettings;

?>