<?php

$params = $getHandler();

$data = $pkg->useResource('list');

$res = getBoardsHandlerEngine($data);

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
echo $res['content'];

?>
