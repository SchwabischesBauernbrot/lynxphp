<?php

// getters

// several places in the code we need data
// and we do a horrible job at checking various caches

function getter_getBoard($uri) {
  global $boardData;
  $out = $boardData;
  //print_r($boardData);
  if (!$boardData || !isset($boardData['uri']) || $boardData['uri'] !== $uri) {
    $out = getBoard($uri);
    $boardData = $out; // put it back in cache
  }
  return $out;
}

function getter_getBoardSettings($uri) {
  global $boards_settings;
  if (!empty($boards_settings[$uri])) {
    //echo "<pre>returning $uri [", print_r($boards_settings[$uri], 1), "]</pre>\n";
    return $boards_settings[$uri];
  }
  $boardData = getter_getBoard($uri);
  $boardSettings = array();
  if (isset($boardData['settings'])) {
    $boardSettings = $boardData['settings'];
    // upload to cache too
    $boards_settings[$uri] = $boardData['settings'];
  } else {
    echo "getter_getBoardSettings[$uri] - boardData missing settings[<pre>", print_r($boardData, 1), "</pre>]\n";
    // if we get 404 we should cache that...
    $boards_settings[$uri] = array(array());
  }
  return $boardSettings;
}

?>