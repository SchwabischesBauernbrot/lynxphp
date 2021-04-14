<?php

// FIXME: we need access to package
$params = $getHandler();

// $boardData = boardOwnerMiddleware($request);

$boardUri = $request['params']['uri'];

// get a list of banners from backend
$logs = $pkg->useResource('list', array('boardUri' => $boardUri));

$templates = moduleLoadTemplates('log_listing', __DIR__);

$tmpl = $templates['header'];
$log_tmpl = $templates['loop0'];
//$boardData = getBoard($boardUri);
global $boardData;
if (empty($boardData)) {
  $boardData = getBoard($boardUri);
}

/*
// FIXME: portal middleware?
$boardHeader_html = renderBoardHeader($boardData);
$boardNav_html = renderBoardNav($boardUri, $boardData['pageCount'], '[Logs]');
$tmpl = $boardHeader_html . $boardNav_html . $tmpl;
*/

$logs_html = '';
if (is_array($logs)) {
  foreach($logs as $l) {
    $tmp = $log_tmpl;
    $tmp = str_replace('{{log}}', print_r($l, 1), $tmp);
    $logs_html .= $tmp;
  }
}
$tmpl = str_replace('{{uri}}', $boardUri, $tmpl);
$tmpl = str_replace('{{logs}}', $logs_html, $tmpl);

$boardHeader = renderBoardPortalHeader($boardUri, $boardData);
wrapContent($boardHeader . $tmpl);
?>
