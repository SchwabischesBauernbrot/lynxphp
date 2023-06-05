<?php

// getters

// several places in the code we need data
// and we do a horrible job at checking various caches

function getter_getBoard($uri) {
  global $boardData;
  $out = $boardData;
  if (!$boardData || $boardData['uri'] !== $uri) {
    $out = getBoard($uri);
    $boardData = $out; // put it back in cache
  }
  return $out;
}

function getter_getBoardSettings($uri) {
  global $boards_settings;
  if (isset($boards_settings[$uri])) {
    return $boards_settings[$uri];
  }
  $boardData = getter_getBoard($uri);
  $boardSettings = array();
  if (isset($boardData['settings'])) {
    $boardSettings = $boardData['settings'];
    // upload to cache too
    $boards_settings[$uri] = $boardData['settings'];
  } else {
    echo "getter_getBoardSettings - boardData missing settings[<pre>", print_r($boardData, 1), "</pre>]\n";
  }
  return $boardSettings;
}

?>