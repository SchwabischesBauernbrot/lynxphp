<?php

$params = $getHandler();

$boardUri = $request['params']['uri'];

// get a list of all posts
$posts = $pkg->useResource('list', array('uri' => $boardUri));


wrapContent('<pre>' . print_r($posts, 1) . "</pre>\n");
?>
