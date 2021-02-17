<?php

$params = $getModule();

global $btLookups;
if (!(isset($btLookups) && is_array($btLookups) && count($btLookups))) {
  return;
}
$btLookups = $pkg->useResource('boardthreadlookup', $btLookups);

//echo "<pre>[", print_r($btLookups, 1), "]</pre>\n";

?>