<?php

$params = $getHandler();

$data = $pkg->useResource('list');

// print_r($data);

$res = getBoardsHandlerEngine($data);
// print_r($res);

wrapContent($res['content'], array('settings' => $res['settings']));

?>
