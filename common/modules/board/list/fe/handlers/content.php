<?php

$params = $getHandler();

$boardsOptions = getBoardsParams();
//print_r($boardsOptions);

$data = $pkg->useResource('list', $boardsOptions);

// print_r($data);
$templates = moduleLoadTemplates('board_listing', __DIR__);

$res = renderBoardsTemplate($data, $templates, $boardsOptions);
// print_r($res);

wrapContent($res['content'], array('settings' => $res['settings']));

?>
