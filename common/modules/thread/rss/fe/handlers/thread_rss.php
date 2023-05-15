<?php

$params = $getHandler();

//print_r($params);
$p = $params['request']['params'];
$boardUri = $p['uri'];
$threadNum = $p['number'];

// get thread / board data
$boardData = getBoardThread($boardUri, $threadNum);
if ($boardData === false) {
  http_response_code(404);
  wrapContent('Board ' . $boardUri . ' does not exist');
  return;
}
// MISSING_BOARD just means no board key in data...
// empty may pick up an valid empty array
if (!isset($boardData['title']) || !isset($boardData['posts']) || $boardData['posts'] === false) {
  http_response_code(404);
  wrapContent('This thread does not exist');
  return;
}
// lynxchan bridge error handling:
// uri and settings: array(), pageCount: 15 will be set
if (!isset($boardData['title'])) {
  http_response_code(404);
  wrapContent('Board ' . $boardUri . ' does not exist');
  return;
}
if (!isset($boardData['posts'])) {
  http_response_code(404);
  wrapContent('This thread does not exist');
  return;
}

// read the rss.tmpl
$templates = moduleLoadTemplates('rss', __DIR__, array('noDev' => true));

// generate thread data
$posts_xml = '';
foreach($boardData['posts'] as $post) {
  $link = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $boardUri . '/thread/' . $threadNum . '.html#' . $post['no'];
  $post_xml = '<item>' . "\n";
  $post_xml .= '  <guid>' . $link . '</guid>' . "\n";
  $title = $post['sub'];
  // can't have &gt;
  $escapedCom = $post['com'];
  if (!$title) $title = '<![CDATA[' . $escapedCom . ']]>';
  $post_xml .= '  <title>' . $title . '</title>' . "\n";
  $post_xml .= '  <link>' . $link . '</link>' . "\n";
  $post_xml .= '  <description><![CDATA[' . $escapedCom . ']]></description>' . "\n";
  $post_xml .= '  <pubDate>' . date("D, d M Y H:i:s", $post['created_at']) . ' GMT</pubDate>' . "\n";
  $post_xml .= '</item>' . "\n";
  $posts_xml .= $post_xml;
}

$boardDescription = htmlspecialchars($boardData['description']);
if ($boardDescription) $boardDescription = '<description>' . $boardDescription . '</description>' . "\n";

// pop in tags
$tags = array(
  'threadTitle' => $boardData['title'],
  'description' => $boardDescription,
  'posts' => $posts_xml,
  'url' => 'https://' . $_SERVER['HTTP_HOST'] . '/' . $boardUri . '/thread/' . $threadNum . '.rss',
);
$html = replace_tags($templates['header'], $tags);

header('Content-type: application/rss+xml');
echo $html;
?>