<?php

// fe side (for lib.backend's expectJson)
$params = $getModule();

// call this function after the portal call
// maybe this is a module call
//echo "<pre>", print_r($io, 1), "</pre>\n";

$meta = $io['resp']['meta'];
$data = $io['resp']['data'];

/*
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

// upload so all other functions can access
global $boards_settings;
$boards_settings[$boardUri] = $boardSettings;
*/

?>