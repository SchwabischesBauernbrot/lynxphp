<?php

include '../frontend_lib/handlers/boards.php'; // preprocessPost

$templates = loadTemplates('thread_listing');

$page_template = $templates['loop0'];
$boardnav_html = $templates['loop1'];
$file_template = $templates['loop2'];
$threadhdr_template = $templates['loop3'];
$threadftr_template = $templates['loop4'];
$thread_template = $templates['loop5'];

$boards_html = '';
$overboardData = $pkg->useResource('overboard');

$nPosts = array();
foreach($overboardData['threads'] as $i => $t) {
  if (!isset($t['posts'])) continue;
  foreach($t['posts'] as $j => $post) {
    //echo "<pre>post[", print_r($post, 1), "]</pre>\n";
    preprocessPost($overboardData['threads'][$i]['posts'][$j]);
    $nPosts[] = $post;
  }
}

global $pipelines;
$post_io = array(
  'posts' => $nPosts,
  //'boardThreads' => $boardThreads,
  //'pagenum' => $pagenum
);
$pipelines[PIPELINE_POST_POSTPREPROCESS]->execute($post_io);
unset($nPosts);

/*
$boards = getBoards();
foreach($boards as $c=>$b) {
  $tmp = $templates['loop0'];
  $boards_html .= $tmp . "\n";
}
*/
$tags = array(
  'uri' => 'overboard',
  'title' => 'Overboard Index',
  'description' => 'content from all of our boards',
  'boards' => $boards_html,
);

$content = replace_tags($templates['header'], $tags);

$threads_html = '';
foreach($overboardData['threads'] as $thread) {
  if (!isset($thread['posts'])) continue;
  //echo "<pre>thread[", print_r($thread, 1), "]</pre>\n";
  $posts = $thread['posts'];
  //echo "count[", count($posts), "]<br>\n";
  $bUri = $thread['boardUri'];
  // we use base tag I believe...
  $threads_html .= '<h2><a href="/' . $bUri . '/">&gt;&gt;&gt;/' . $bUri . '/</a></h2>' . $threadhdr_template;
  foreach($posts as $i => $post) {
    $threads_html .= renderPost($bUri, $post, array(
      'checkable' => true, 'postCount' => $thread['thread_reply_count'],
    ));
  }
  $threads_html .= $threadftr_template;
}

wrapContent($threads_html);