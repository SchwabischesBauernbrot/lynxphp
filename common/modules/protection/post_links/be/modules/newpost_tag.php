<?php

$params = $getModule();

// do we have links?
$msg = $io['p']['com'];
//echo "looking at [$msg]<br>\n";

$regex = "((https?|ftp)\:\/\/)?"; // SCHEME
$regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
$regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
$regex .= "(\:[0-9]{2,5})?"; // Port
$regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
$regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
$regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor

if (preg_match('/' . $regex . '/', $msg)) {
  // add tag
  //echo "has link<br>\n";
  $io['tags']['has_links'] = true;
} else {
  $io['tags']['has_links'] = false;
}

?>