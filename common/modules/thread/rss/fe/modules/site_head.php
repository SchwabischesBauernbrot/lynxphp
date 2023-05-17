<?php

$params = $getModule();

// io has siteSettings, userSettings and head_html
// params has options (which is empty)

if (strpos($_SERVER['REQUEST_URI'], '/thread/') !== false) {
  $io['head_html'] .= '<link rel="alternative" type="application/rss+xml" href="' . str_replace('.html', '', $_SERVER['REQUEST_URI']) . '.rss" title="RSS Feed for this board">' . "\n";
}

?>