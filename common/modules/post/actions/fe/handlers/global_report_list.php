<?php

$params = $getHandler();

// FIXME: requires global access...

// get a list of reports from backend
// category filter?
$result = $pkg->useResource('open_reports', array('global' => true));

//echo "<pre>Result[", print_r($result, 1), "]</pre>\n";

$templates = moduleLoadTemplates('report_listing', __DIR__);

$header = $templates['header'];
$report_tmpl = $templates['loop0'];

$tmpl = $header;

$reports_html = '';
foreach ($result['reports'] as $r) {
  // _id, global, boardUri, threadId, postId, creaation
  $threads = getBoardThread($r['boardUri'], $r['threadId']);
  $thisPost = array_filter($threads['posts'], function($t) use ($r) {
    return $t['no'] === $r['postId'];
  });
  if (count($thisPost)) {
    $keys = array_keys($thisPost);
    $thisPost = $thisPost[$keys[0]];
  }
  // close report...
  $tmp = $report_tmpl;
  $tmp = str_replace('{{uri}}', $r['boardUri'], $tmp);

  $tmp = str_replace('{{_id}}', $r['_id'], $tmp);
  $tmp = str_replace('{{zebra}}', ($r['_id'] % 2 === 1) ? 'odd' : 'even', $tmp);
  $tmp = str_replace('{{global}}', $r['global'], $tmp);
  $tmp = str_replace('{{boardUri}}', $r['boardUri'], $tmp);
  $tmp = str_replace('{{threadId}}', $r['threadId'], $tmp);
  $tmp = str_replace('{{postId}}', $r['postId'], $tmp);
  $tmp = str_replace('{{creation}}', $r['creation'], $tmp);
  $tmp = str_replace('{{post}}', renderPost($r['boardUri'], $thisPost), $tmp);
  $reports_html .= $tmp;
}
$tmpl = str_replace('{{backURL}}', 'global.php', $tmpl);
$tmpl = str_replace('{{reports}}', $reports_html, $tmpl);

wrapContent($tmpl);

/*

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
*/
?>
