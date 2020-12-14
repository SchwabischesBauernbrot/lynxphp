<?php

// FIXME: we need access to package
$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;
// get a list of banners from backend

global $beRrsc_list;
$call = $beRrsc_list;
// FIXME:
$call['endpoint'] .= '?boardUri=' . $boardUri;
$banners = consume_beRsrc($call, array('boardUri' => $boardUri));

$templates = moduleLoadTemplates('banner_listing', __DIR__);

// FIXME: include board header...
// FIXME: include paged board nav...

$header = $templates['header'];
$banner_tmpl = $templates['loop1'];
$tmpl = str_replace('{{banners}}', $header, $templates['loop2']);
// add link
// list
$banners_html = '';
foreach($banners as $banner) {
  $tmp = $banner_tmpl;
  $tmp = str_replace('{{backend}}', 'backend', $tmp);
  $tmp = str_replace('{{uri}}', $boardUri, $tmp);
  $tmp = str_replace('{{id}}', $banner['bannerid'], $tmp);
  $tmp = str_replace('{{image}}', $banner['image'], $tmp);
  $banners_html .= $tmp;
}
$tmpl = str_replace('{{uri}}', $boardUri, $tmpl);
$tmpl = str_replace('{{banners}}', $banners_html, $tmpl);
wrapContent($tmpl);

?>
