<?php

$params = $getModule();

global $btLookups;
if (!(isset($btLookups) && is_array($btLookups) && count($btLookups))) {
  return;
}

//echo "<pre>btLookups in[", print_r($btLookups, 1), "]</pre>\n";
$inbtLookups = $btLookups; // copies

$btLookups = $pkg->useResource('boardthreadlookup', $btLookups);

// ensure the structure is set
foreach($inbtLookups as $board => $posts) {
  //echo "looking [$board]<br>\n";
  if (!isset($btLookups[$board])) {
    $btLookups[$board] = array();
  }
  foreach($posts as $p => $t) {
    if (!isset($btLookups[$board][$p])) {
      $btLookups[$board][$p] = false;
    }
  }
}
//echo "<pre>btLookups out[", print_r($btLookups, 1), "]</pre>\n";

?>