<?php

include '../frontend_lib/handlers/boards.php'; // preprocessPost

$boardUri = $request['params']['uri'];
$threadNum = (int)str_replace('_inline', '', $request['params']['num_inline']);

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
// remove op
array_shift($boardData['posts']);
foreach($boardData['posts'] as $post) {
  //echo "<pre>", print_r($post, 1), "</pre>\n";
  $tmp = $post_template;
  $posts_html .= renderPost($boardUri, $post, array(
    'checkable' => true, 'postCount' => $cnt,
    'noOmit' => true, 'boardSettings' => $boardData['settings'],
  ));
  $files += count($post['files']);
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

// FIXME: move into a pipeline
$closed = false;
if (count($boardData['posts'])) {
  $closed = empty($boardData['posts'][0]['closed']) ? false : true;
}

// this will include all scripts, not just this one...
js_add_script($pkg, 'refresh_thread.js');

global $BASE_HREF;
$row = wrapContentData(array());
$head_html = wrapContentGetHeadHTML($row);

echo <<<EOB
<!DOCTYPE html>
<html>
<head id="settings">
  <base href="$BASE_HREF" target="_parent">
  $head_html
</head>
<body id="top">
EOB;
echo $tmpl;
echo <<<EOB
EOB;
wrapContentFooter($row);
//wrapContent($tmpl);
// probably need to include the regular JS