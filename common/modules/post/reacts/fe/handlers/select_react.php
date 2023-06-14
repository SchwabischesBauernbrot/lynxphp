<?php

$params = $getHandler();

$uri = $params['request']['params']['uri'];
$threadId = $params['request']['params']['threadId'];
$postId = $params['request']['params']['postId'];
//$react = $params['request']['params']['react'];

$reacts = json_decode(file_get_contents(__DIR__ . '/../data-by-emoji.json'), true);

$link = $uri . '/thread/' . $threadId . '/' . $postId . '/react';

// search
// FIXME: recently used
// favorites

$q = getQueryField('q');

$str = '<meta name="robots" content="noindex">';
$str .= '<form method="GET" action="' . $link . '">';
$str .= '<input type="text" name="q" value="' . $q . '">';
$str .= '<input type="submit" value="Search">';
$str .= '</form>';

$boardReacts = $pkg->useResource('list', array('boardUri' => $uri));

// FIXME: filter here
// then count...

if (count($boardReacts)) {
  $str .= 'Board specific Reacts: ';
  foreach($boardReacts as $r) {
    if (!$r['hide_default'] && !$r['lock_default']) {
      // text
      $e = $r['text'];
      $str .= '<span title="' . $r['name'] . '"><a href="' . $link . '/' . $e .'">' . $e . '</a></span> ';
    }
  }
  $str .= '<br>' . "\n";
}

// FIXME: make sure mode isn't custom only
$str .= 'Select React: ';
foreach($reacts as $e => $r) {
  // name, slug, group, emoji_version, unicode_version, skin_tone_support
  if ($q) {
    if (stripos($r['name'], $q) !== false) {
      $str .= '<span title="' . $r['name'] . '"><a href="' . $link . '/' . $e .'">' . $e . '</a></span>';
    }
  } else {
    $str .= '<span title="' . $r['name'] . '"><a href="' . $link . '/' . $e .'">' . $e . '</a></span>';
  }
}

wrapContent($str);

?>