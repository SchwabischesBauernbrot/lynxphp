<?php

$params = $getHandler();

$boardUri = $params['request']['params']['uri'];
$tno = $params['request']['params']['tno'];

// request scrub from backend
$res = $pkg->useResource('undel_thread', array('uri' => $boardUri, 'tno' => $tno));

// after it's done being nuked back to the deleted threads list
//wrapContent(print_r($res, 1));

// we have a from query tbh
redirectTo('/' . $boardUri . '/thread/' . $tno . '.html');
///:uri/threads/deleted