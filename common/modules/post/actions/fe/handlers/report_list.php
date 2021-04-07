<?php

// FIXME: we need access to package
$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

// get a list of reports from backend
// category filter?
$result = $pkg->useResource('open_reports', array('boardUri' => $boardUri));

$templates = moduleLoadTemplates('report_listing', __DIR__);

$header = $templates['header'];
$report_tmpl = $templates['loop0'];

$tmpl = $header;

$reports_html = '';
foreach ($result['reports'] as $r) {
  //echo "<pre>r:", print_r($r, 1), "</pre>\n";
  // _id, global, boardUri, threadId, postId, creaation
  $thread = getBoardThread($boardUri, $r['threadId']);
  //echo "<pre>", print_r($thread, 1), "</pre>\n";
  $thisPost = array_filter($thread['posts'], function($t) use ($r) {
    return $t['no'] === $r['postId'];
  });
  //echo "<pre>", print_r($thisPost, 1), "</pre>\n";
  if (count($thisPost)) {
    $keys = array_keys($thisPost);
    $thisPost = $thisPost[$keys[0]];
  }
  // close report...
  $tmp = $report_tmpl;
  $tmp = str_replace('{{uri}}', $boardUri, $tmp);

  $tmp = str_replace('{{_id}}', $r['_id'], $tmp);
  //$tmp = str_replace('{{zebra}}', ($r['_id'] % 2 === 1) ? 'odd' : 'even', $tmp);
  $tmp = str_replace('{{global}}', $r['global'], $tmp);
  $tmp = str_replace('{{threadId}}', $r['threadId'], $tmp);
  $tmp = str_replace('{{postId}}', $r['postId'], $tmp);
  $tmp = str_replace('{{creation}}', $r['creation'], $tmp);
  $tmp = str_replace('{{post}}', renderPost($boardUri, $thisPost), $tmp);
  $reports_html .= $tmp;
}
$tmpl = str_replace('{{backURL}}', $boardUri . '/settings', $tmpl);
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
