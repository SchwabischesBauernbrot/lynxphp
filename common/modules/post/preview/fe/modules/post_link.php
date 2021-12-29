<?php

$params = $getModule();

//print_r($io);

// FIXME: SEO slug

// nginx
// REDIRECT_URL
// REQUEST_URI
// PATH_INFO
// PATH_TRANSLATED
// PHPS_ELF
$on_preview_page = strpos($_SERVER['REQUEST_URI'], '/preview/') !== false;
if (!$on_preview_page) {
  $io['links'][] = array('label' => '[Preview]', 'link' => '/' . $io['boardUri'] . '/preview/' . $io['p']['no'] . '.html');
}
// else we already have No and number on the page...

?>