<?php

// post_links/be

$module = $getModule();

$text = $io['post']['com'];

$io['links_has'] = post_links_has($text);
$io['links_found'] = post_links_get($text);

?>