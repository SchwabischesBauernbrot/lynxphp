<?php

$params = $getHandler();

//print_r($params['request']['params']);
$uri = $params['request']['params']['uri'];
//$tid = $params['request']['params']['threadid'];
$pid = $params['request']['params']['postid'];

// we have to check the backend whereever it may be...
// this is a bit less bw intensive and more controlled-caching
$thumbs = $pkg->useResource('thumbnail_ready', array('uri' => $uri, 'pid' => $pid));

//$thumbs['path'] = 'storage/boards/' . $uri . '/t_' . $tid;

echo json_encode($thumbs);

//wrapContent(print_r($thumbs, 1));

?>