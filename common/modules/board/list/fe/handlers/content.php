<?php

$params = $getHandler();

$boardsOptions = getBoardsParams();
//print_r($boardsOptions);

$data = $pkg->useResource('list', $boardsOptions);

// print_r($data);

$res = getBoardsHandlerEngine($data, $boardsOptions);
// print_r($res);

wrapContent($res['content'], array('settings' => $res['settings']));

?>
