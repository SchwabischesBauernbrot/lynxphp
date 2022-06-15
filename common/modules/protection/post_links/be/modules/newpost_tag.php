<?php

$params = $getModule();

// do we have links?
$msg = $io['p']['com'];
//echo "looking at [$msg]<br>\n";

if (post_links_has($msg)) {
  // add tag
  //echo "has link<br>\n";
  $io['tags']['has_links'] = true;
} else {
  $io['tags']['has_links'] = false;
}

?>