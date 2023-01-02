<?php

include '../frontend_lib/handlers/boards.php'; // preprocessPost

$boardUri = $request['params']['uri'];
$threadNum = (int)str_replace('.html', '', $request['params']['num']);

$templates = loadTemplates('thread_details');
$tmpl = $templates['header'];

//$boardNav_template = $templates['loop0'];
$file_template = $templates['loop1'];
$hasReplies_template = $templates['loop2'];
$reply_template = $templates['loop3'];
$post_template = $templates['loop4'];

/*
$tmp = $boardNav_template;
$tmp = str_replace('{{uri}}', $boardUri, $tmp);
$boardnav_html = $tmp;
*/

$boardData = getBoardThread($boardUri, $threadNum);
if ($boardData === false) {
  // 404
  http_response_code(404);
  wrapContent('Board ' . $boardUri . ' does not exist');
  return;
}
// MISSING_BOARD just means no board key in data...
if ($boardData['posts'] === false) {
  // 404
  http_response_code(404);
  wrapContent('This thread does not exist');
  return;
}
// lynxchan bridge error handling:
// uri and settings: array(), pageCount: 15 will be set
if (!isset($boardData['title'])) {
  // 404
  http_response_code(404);
  wrapContent('Board ' . $boardUri . ' does not exist');
  return;
}
if (!isset($boardData['posts'])) {
  // 404
  http_response_code(404);
  wrapContent('This thread does not exist');
  return;
}
//echo "<pre>", $boardData['sageLimit'], "</pre>\n";

$sageLimit  = empty($boardData['sageLimit']) ? 500 : $boardData['sageLimit'];
$replyLimit = empty($boardData['replyLimit']) ? 1000 : $boardData['replyLimit'];

foreach($boardData['posts'] as $j => $post) {
  preprocessPost($boardData['posts'][$j]);
}
global $pipelines;
$data = array(
  'posts' => $boardData['posts'],
  'boardData' => $boardData,
  'threadNum' => $threadNum
);
$pipelines[PIPELINE_POST_POSTPREPROCESS]->execute($data);

$posts_html = '';
$files = 0;
$cnt = count($boardData['posts']);
$closed = false;
// FIXME: move into a pipeline
if (count($boardData['posts'])) {
  $closed = empty($boardData['posts'][0]['closed']) ? false : true;
}
if ($cnt > $replyLimit) {
  $closed = true;
}
$saged = $cnt > $sageLimit;
//echo "cnt[$cnt / $sageLimit / $replyLimit]<br>\n";
foreach($boardData['posts'] as $post) {
  //echo "<pre>", print_r($post, 1), "</pre>\n";
  $tmp = $post_template;
  $posts_html .= renderPost($boardUri, $post, array(
    'checkable' => true, 'postCount' => $cnt,
    'noOmit' => true, 'boardSettings' => $boardData['settings'],
  ));
  if (isset($post['files'])) {
    $files += count($post['files']);
  }
}

$p = array(
  'boardUri' => $boardUri,
  'tags' => array(
    // need this for form actions
    'uri' => $boardUri,
    'threadNum' => $threadNum,
    'title' => htmlspecialchars($boardData['title']),
    'description' => htmlspecialchars($boardData['description']),
    //$tmpl = str_replace('{{boardNav}}', $boardnav_html, $tmpl);
    'posts' => $posts_html,
    'replies' => count($boardData['posts']) - 1,
    'files' => $files,
    // mixins
    //'postform' => renderPostForm($boardUri, $boardUri . '/catalog'),
    'postactions' => renderPostActions($boardUri),
  )
);
global $pipelines;
$pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);
$tmpl = replace_tags($tmpl, $p['tags']);

$boardPortal = getBoardPortal($boardUri, $boardData, array(
  'isThread' => true,
  'threadNum' => $threadNum,
  'threadClosed' => $closed,
  'threadSaged'  => $saged,
  'maxMessageLength' => $boardData['maxMessageLength'],
));

// this will include all scripts, not just this one...
js_add_script($pkg, 'refresh_thread.js');

wrapContent($boardPortal['header'] . $tmpl . $boardPortal['footer']);