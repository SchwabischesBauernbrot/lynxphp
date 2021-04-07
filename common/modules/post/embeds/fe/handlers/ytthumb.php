<?php

// FIXME: we need access to package
$params = $getHandler();

//print_r($params);

$videoId = $params['request']['params']['videoid'];

header('Content-type: image/jpeg');
readfile('https://img.youtube.com/vi/' . $videoId . '/default.jpg');

?>