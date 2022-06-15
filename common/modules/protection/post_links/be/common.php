<?php

// post_links/be

function post_links_has($text) {
  $regex = "((https?|ftp)\:\/\/)?"; // SCHEME
  $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
  $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
  $regex .= "(\:[0-9]{2,5})?"; // Port
  $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
  $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
  $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor
  return preg_match('/' . $regex . '/', $text);
}

function post_links_get($text) {
  // https://stackoverflow.com/a/36564776
  if (preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#i', $text, $match)) {
    return $match[0];
  }
  return array(); // none
}

?>
