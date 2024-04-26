<?php

$params = $getHandler();

$boardUri = $params['request']['params']['uri'];
$pno = $params['request']['params']['pno'];

// request scrub from backend
$res = $pkg->useResource('scrub_post', array('uri' => $boardUri, 'pno' => $pno));

// after it's done being nuked back to the deleted threads list
//wrapContent(print_r($res, 1));

// we have a from query tbh
redirectTo('/' . $boardUri . '/threads/deleted');
///:uri/threads/deleted