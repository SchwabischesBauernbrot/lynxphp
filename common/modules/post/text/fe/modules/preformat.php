<?php

$params = $getModule();

//echo "<pre>post data[", print_r($io, 1), "]</pre>\n";

if (strpos($io['com'], '>>>') === false) {
  return;
}

global $btLookups;
if (!isset($btLookups)) {
  $btLookups = array();
}

preg_match_all('/' . preg_quote('>>>') . '\/?(\w+)\/(\d+)\/?(\s*)/m', $io['com'], $quotes, PREG_SET_ORDER);
foreach($quotes as $i=>$q) {
  //echo "<pre>$i => ", print_r($quote, 1), "</pre>\n";
  $btLookups[$q[1]][$q[2]] = true;
}
/*
if (count($btLookups)) {
  echo "<pre>", print_r($btLookups, 1), "</pre>\n";
}
*/

?>