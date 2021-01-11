<?php

// FIXME: we need access to package
$params = $getHandler();

// $boardData = boardOwnerMiddleware($request);

$boardUri = $request['params']['uri'];

// get a list of banners from backend
$banners = $pkg->useResource('list', array('boardUri' => $boardUri));

$templates = moduleLoadTemplates('log_listing', __DIR__);

$tmpl = $templates['header'];
$banner_tmpl = $templates['loop0'];
$boardData = getBoard($boardUri);

// FIXME: portal middleware?
$boardHeader_html = renderBoardHeader($boardData);
$boardNav_html = renderBoardNav($boardUri, $boardData['pageCount'], '[Logs]');
$tmpl = $boardHeader_html . $boardNav_html . $tmpl;

$banners_html = '';
if (is_array($banners)) {
  foreach($banners as $banner) {
    $tmp = $banner_tmpl;
    $tmp = str_replace('{{backend}}', 'backend', $tmp);
    $tmp = str_replace('{{uri}}', $boardUri, $tmp);
    $tmp = str_replace('{{id}}', $banner['bannerid'], $tmp);
    $tmp = str_replace('{{image}}', $banner['image'], $tmp);
    $banners_html .= $tmp;
  }
}
$tmpl = str_replace('{{uri}}', $boardUri, $tmpl);
$tmpl = str_replace('{{banners}}', $banners_html, $tmpl);
wrapContent($tmpl);
?>
