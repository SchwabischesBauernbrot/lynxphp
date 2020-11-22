<?php

function getBoardsHandler() {
  $boards = getBoards();
  $templates = loadTemplates('board_listing');
  $boards_html = '';
  foreach($boards as $c=>$b) {
    $tmp = $templates['loop0'];
    $tmp = str_replace('{{uri}}', $b['uri'], $tmp);
    $tmp = str_replace('{{title}}', htmlspecialchars($b['title']), $tmp);
    $tmp = str_replace('{{description}}', htmlspecialchars($b['description']), $tmp);
    $boards_html .= $tmp . "\n";
  }

  $content = $templates['header'];
  $content = str_replace('{{boards}}', $boards_html, $content);

  wrapContent($content);
}

// move into a module...
function getOverboardHandler() {
  $templates = loadTemplates('thread_listing');
  /*
  $boards = getBoards();
  $boards_html = '';
  foreach($boards as $c=>$b) {
    $tmp = $templates['loop0'];
    $boards_html .= $tmp . "\n";
  }
  */
  $content = $templates['header'];
  $content = str_replace('{{uri}}', 'overboard', $content);
  $content = str_replace('{{title}}', 'Overboard Index', $content);
  $content = str_replace('{{description}}', $b['description'], $content);
  $content = str_replace('{{boards}}', $boards_html, $content);

  wrapContent($content);
}

function getBoardPageHandler($boardUri, $pagenum, $pageData = null) {
  if ($pageData === null) {
    $pageData = getBoardPage($boardUri, $pagenum);
  }
  $templates = loadTemplates('thread_listing');
  //echo join(',', array_keys($templates));

  $page_template = $templates['loop0'];
  $boardnav_html = $templates['loop1'];
  $thread_template = $templates['loop3'];

  // loop 0 goes into this html...
  $pages_html = '';

  // FIXME: get page count...
    $tmp = $page_template;
    $tmp = str_replace('{{uri}}', $boardUri, $tmp);
    // bold
    $tmp = str_replace('{{class}}', $pagenum == 1 ? 'bold' : '', $tmp);
    $tmp = str_replace('{{pagenum}}', 1, $tmp);
    $pages_html .= $tmp;

  $boardnav_html = str_replace('{{pages}}', $pages_html, $boardnav_html);

  $threads_html = '';
  foreach($pageData as $thread) {
    $tmp = $thread_template;
    $tmp = str_replace('{{subject}}', htmlspecialchars($thread['sub']),  $tmp);
    $tmp = str_replace('{{message}}', htmlspecialchars($thread['com']),  $tmp);
    $tmp = str_replace('{{name}}',    htmlspecialchars($thread['name']), $tmp);
    $tmp = str_replace('{{no}}',      $thread['no'],   $tmp);
    $tmp = str_replace('{{uri}}', $boardUri, $tmp);
    $tmp = str_replace('{{jstime}}', date('c', $thread['created_at']), $tmp);
    $tmp = str_replace('{{human_created_at}}', date('n/j/Y H:i:s', $thread['created_at']), $tmp);
    $files_html = '';
    $tmp = str_replace('{{files}}', $files_html, $tmp);
    $threads_html .= $tmp;
  }

  $tmpl = $templates['header'];
  $boardData = getBoard($boardUri);
  $tmpl = str_replace('{{uri}}', $boardUri, $tmpl);
  $tmpl = str_replace('{{title}}', htmlspecialchars($boardData['title']), $tmpl);
  $tmpl = str_replace('{{description}}', htmlspecialchars($boardData['description']), $tmpl);
  $tmpl = str_replace('{{threads}}', $threads_html, $tmpl);
  $tmpl = str_replace('{{boardNav}}', $boardnav_html, $tmpl);

  wrapContent($tmpl);
}

function getThreadHandler($boardUri, $threadNum) {
  $threadNum = (int)$threadNum;
  $templates = loadTemplates('thread_details');
  $tmpl = $templates['header'];
  $boardData = getBoard($boardUri);

  $tmp = $templates['loop0'];
  $tmp = str_replace('{{uri}}', $boardUri, $tmp);
  $boardnav_html = $tmp;

  $posts = getBoardThread($boardUri, $threadNum);
  $posts_html = '';
  foreach($posts as $post) {
    $tmp = $templates['loop3'];
    $tmp = str_replace('{{subject}}', htmlspecialchars($post['sub']),  $tmp);
    $tmp = str_replace('{{message}}', htmlspecialchars($post['com']),  $tmp);
    $tmp = str_replace('{{name}}',    htmlspecialchars($post['name']), $tmp);
    $tmp = str_replace('{{no}}',      $post['no'],   $tmp);
    $tmp = str_replace('{{uri}}', $boardUri, $tmp);
    $tmp = str_replace('{{threadNum}}', $threadNum, $tmp);
    $tmp = str_replace('{{jstime}}', date('c', $post['created_at']), $tmp);
    $tmp = str_replace('{{human_created_at}}', date('n/j/Y H:i:s', $post['created_at']), $tmp);
    $files_html = '';
    foreach($post['files'] as $file) {
      $ftmpl = $templates['loop2'];
      // disbale images until we can mod...
      //$ftmpl = str_replace('{{path}}', 'backend/' . $file['path'], $ftmpl);
      $files_html .= $ftmpl;
    }
    $tmp = str_replace('{{files}}', $files_html, $tmp);
    $replies_html = '';
    $tmp = str_replace('{{replies}}', $replies_html, $tmp);
    $posts_html .= $tmp;
  }

  $tmpl = str_replace('{{uri}}', $boardUri, $tmpl);
  $tmpl = str_replace('{{threadNum}}', $threadNum, $tmpl);
  $tmpl = str_replace('{{title}}', htmlspecialchars($boardData['title']), $tmpl);
  $tmpl = str_replace('{{description}}', htmlspecialchars($boardData['description']), $tmpl);
  $tmpl = str_replace('{{boardNav}}', $boardnav_html, $tmpl);
  $tmpl = str_replace('{{posts}}', $posts_html, $tmpl);
  wrapContent($tmpl);
}

?>
