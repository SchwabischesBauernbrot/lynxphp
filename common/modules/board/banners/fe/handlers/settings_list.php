<?php

$params = $getHandler();

//echo "<pre>", print_r($params, 1), "</pre>\n";

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

// get a list of banners from backend
$banners = $pkg->useResource('list', array('boardUri' => $boardUri));

$templates = moduleLoadTemplates('banner_listing', __DIR__);

$header = $templates['header'];
$banner_tmpl = $templates['loop1'];

// insert loop2 into header
$tmpl = str_replace('{{banners}}', $header, $templates['loop2']);

// add link
// list
$banners_html = '';
foreach($banners as $banner) {
  $banners_html .= replace_tags($banner_tmpl, array(
    //'backend' =>
    'uri'   => $boardUri,
    'id'    => $banner['bannerid'],
    'image' => BACKEND_PUBLIC_URL . $banner['image'],
  ));
}

$tmpl = replace_tags($tmpl, array(
  'uri' => $boardUri,
  'banners' => $banners_html,
));

wrapContent($tmpl);

?>
