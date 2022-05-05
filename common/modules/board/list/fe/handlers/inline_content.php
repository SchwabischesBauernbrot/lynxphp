<?php

$params = $getHandler();

$data = $pkg->useResource('list');

$templates = moduleLoadTemplates('inline_boards', __DIR__);
$params = getBoardsParams();
$res = renderBoardsTemplate($data, $templates, $params);

//$res = getBoardsHandlerEngine($data);

$row = wrapContentData(array());
//wrapContent($res['content'], array('settings' => $res['settings']));
$head_html = wrapContentGetHeadHTML($row);
global $BASE_HREF;
echo <<<EOB
<!DOCTYPE html>
<html>
<head id="settings">
  <base href="$BASE_HREF" target="_parent">
  $head_html
</head>
<body id="top">
EOB;
// maybe auto-size the height based on the number of boards...
echo $res['content'];

?>
