<?php

$params = $getModule();

if (0 && DEV_MODE) {
  echo "<pre>processing post data", print_r($io, 1), "</pre>\n";
}

if (strpos($io['com'], '>>') === false) {
  return;
}

global $btLookups;
if (!isset($btLookups)) {
  $btLookups = array();
}

// one user uses >>NUM to reference another thread
// and now we have to do all this...
if (isset($io['boardUri'])) {
  //echo "<pre>processing", print_r($io, 1), "</pre>\n";
  preg_match_all('/' . preg_quote('>>') . '(\d+)\/?(\s*)/m', $io['com'], $quotes, PREG_SET_ORDER);
  foreach($quotes as $i=>$q) {
    //echo "<pre>$i => ", print_r($q, 1), "</pre>\n";
    //$wholeStrMatch = $q[0];
    $pno = $q[1];
    //$ws = $q[2];
    $btLookups[$io['boardUri']][$pno] = true;
  }
} else {
  if (DEV_MODE) {
    echo 'preformat called (preprocessPost?) without boardUri passed<br>', "\n";
  }
}

preg_match_all('/' . preg_quote('>>>') . '\/?(\w+)\/(\d+)\/?(\s*)/m', $io['com'], $quotes, PREG_SET_ORDER);
foreach($quotes as $i=>$q) {
  //echo "<pre>$i => ", print_r($q, 1), "</pre>\n";
  $btLookups[$q[1]][$q[2]] = true;
}
/*
if (count($btLookups)) {
  echo "<pre>", print_r($btLookups, 1), "</pre>\n";
}
*/

?>