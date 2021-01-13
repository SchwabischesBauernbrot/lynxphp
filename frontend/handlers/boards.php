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
  $boards_html = '';
  /*
  $boards = getBoards();
  foreach($boards as $c=>$b) {
    $tmp = $templates['loop0'];
    $boards_html .= $tmp . "\n";
  }
  */
  $content = $templates['header'];
  $content = str_replace('{{uri}}', 'overboard', $content);
  $content = str_replace('{{title}}', 'Overboard Index', $content);
  $content = str_replace('{{description}}', 'content from all of our boards', $content);
  $content = str_replace('{{boards}}', $boards_html, $content);

  wrapContent($content);
}

// /:uri/
function getBoardThreadListing($boardUri, $pagenum = 1) {
  $boardThreads = backendGetBoardThreadListing($boardUri, $pagenum);
  //echo "<pre>", print_r($boardThreads, 1), "</pre>\n";
  $pageData = $boardThreads['page1'];
  $pages = $boardThreads['pageCount'];
  $boardData = $boardThreads['board'];

  $templates = loadTemplates('thread_listing');
  //echo join(',', array_keys($templates));

  $page_template = $templates['loop0'];
  $boardnav_html = $templates['loop1'];
  $file_template = $templates['loop2'];
  $threadhdr_template = $templates['loop3'];
  $threadftr_template = $templates['loop4'];
  $thread_template = $templates['loop5'];

  //echo "test[", htmlspecialchars(print_r($templates, 1)),"]<br>\n";

  /*
  $pages_html = '';
  //echo "pages[", $boardThreads['pageCount'], "]<br>\n";
  for($p = 1; $p <= $boardThreads['pageCount']; $p++) {
    $tmp = $page_template;
    $tmp = str_replace('{{uri}}', $boardUri, $tmp);
    // bold
    $tmp = str_replace('{{class}}', $pagenum == $p ? 'bold' : '', $tmp);
    $tmp = str_replace('{{pagenum}}', $p, $tmp);
    $pages_html .= $tmp;
  }

  $boardnav_html = str_replace('{{pages}}', $pages_html, $boardnav_html);
  $boardnav_html = str_replace('{{uri}}',   $boardUri,   $boardnav_html);
  */
  $boardnav_html = renderBoardNav($boardUri, $boardThreads['pageCount'], $pagenum);

  $threads_html = '';
  foreach($pageData as $thread) {
    if (!isset($thread['posts'])) continue;
    $posts = $thread['posts'];
    //echo "count[", count($posts), "]<br>\n";
    $threads_html .= $threadhdr_template;
    foreach($posts as $i => $post) {
      $tmp = $thread_template;
      $tmp = str_replace('{{op}}',      $i === 0 ? 'op' : '', $tmp);
      $tmp = str_replace('{{subject}}', htmlspecialchars($post['sub']),  $tmp);
      $tmp = str_replace('{{message}}', htmlspecialchars($post['com']),  $tmp);
      $tmp = str_replace('{{name}}',    htmlspecialchars($post['name']), $tmp);
      $tmp = str_replace('{{no}}',        $post['no'],   $tmp);
      $tmp = str_replace('{{threadNum}}', empty($post['threadid']) ? $post['no'] : $post['threadid'], $tmp);
      $tmp = str_replace('{{uri}}', $boardUri, $tmp);
      $tmp = str_replace('{{jstime}}', date('c', $post['created_at']), $tmp);
      $tmp = str_replace('{{human_created_at}}', date('n/j/Y H:i:s', $post['created_at']), $tmp);
      $files_html = '';
      foreach($post['files'] as $file) {
        $ftmpl = $file_template;
        // disable images until we can mod...
        $ftmpl = str_replace('{{path}}', 'backend/' . $file['path'], $ftmpl);
      $ftmpl = str_replace('{{filename}}', $file['filename'], $ftmpl);
      $ftmpl = str_replace('{{size}}', $file['size'], $ftmpl);
      $ftmpl = str_replace('{{width}}', $file['w'], $ftmpl);
      $ftmpl = str_replace('{{height}}', $file['h'], $ftmpl);
        $files_html .= $ftmpl;
      }
      //echo "<pre>", $post['no'], "files_tempate[", htmlspecialchars($files_html), "] count[", count($post['files']),"] data[",print_r($post['files'], 1),"] [",print_r($file_template, 1),"]</pre>\n";
      $tmp = str_replace('{{files}}', $files_html, $tmp);
      $threads_html .= $tmp;
    }
    $threads_html .= $threadftr_template;
  }

  $tmpl = $templates['header'];

  $p = array(
    'boardUri' => $boardUri,
    'tags' => array()
  );
  global $pipelines;
  $pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);
  foreach($p['tags'] as $s => $r) {
    $tmpl = str_replace('{{' . $s . '}}', $r, $tmpl);
  }

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

  $boardNav_template = $templates['loop0'];
  $file_template = $templates['loop1'];
  $hasReplies_template = $templates['loop2'];
  $reply_template = $templates['loop3'];
  $post_template = $templates['loop4'];

  $tmp = $boardNav_template;
  $tmp = str_replace('{{uri}}', $boardUri, $tmp);
  $boardnav_html = $tmp;

  $posts = getBoardThread($boardUri, $threadNum);
  $posts_html = '';
  foreach($posts as $post) {
    //echo "<pre>", print_r($post, 1), "</pre>\n";
    $tmp = $post_template;
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
      $ftmpl = $file_template;
      //print_r($file);
      // disbale images until we can mod...
      $ftmpl = str_replace('{{path}}', 'backend/' . $file['path'], $ftmpl);
      $ftmpl = str_replace('{{filename}}', $file['filename'], $ftmpl);
      $ftmpl = str_replace('{{size}}', $file['size'], $ftmpl);
      $ftmpl = str_replace('{{width}}', $file['w'], $ftmpl);
      $ftmpl = str_replace('{{height}}', $file['h'], $ftmpl);
      $files_html .= $ftmpl;
    }
    $tmp = str_replace('{{files}}', $files_html, $tmp);
    $replies_html = '';
    $tmp = str_replace('{{replies}}', $replies_html, $tmp);
    $posts_html .= $tmp;
  }

  $boardData = getBoard($boardUri);
  $p = array(
    'boardUri' => $boardUri,
    'tags' => array()
  );
  global $pipelines;
  $pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);
  foreach($p['tags'] as $s => $r) {
    $tmpl = str_replace('{{' . $s . '}}', $r, $tmpl);
  }

  $tmpl = str_replace('{{uri}}', $boardUri, $tmpl);
  $tmpl = str_replace('{{threadNum}}', $threadNum, $tmpl);
  $tmpl = str_replace('{{title}}', htmlspecialchars($boardData['title']), $tmpl);
  $tmpl = str_replace('{{description}}', htmlspecialchars($boardData['description']), $tmpl);
  $tmpl = str_replace('{{boardNav}}', $boardnav_html, $tmpl);
  $tmpl = str_replace('{{posts}}', $posts_html, $tmpl);
  wrapContent($tmpl);
}

function getBoardCatalogHandler($boardUri) {
  $catalog = getBoardCatalog($boardUri);
  if (!empty($catalog['meta']['err'])) {
    if ($catalog['meta']['err'] === 'Board not found') {
      wrapContent("Board not found");
    } else {
      wrapContent("Unknown board error");
    }
    return;
  }
  $templates = loadTemplates('catalog');

  $tmpl = $templates['header'];

  $boardnav_html  = $templates['loop0'];
  $image_template = $templates['loop1'];
  $tile_template  = $templates['loop2'];

  /*
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
  */
  //$boardnav_html = str_replace('{{uri}}',   $boardUri,   $boardnav_html);

  $maxPage = 0;
  foreach($catalog as $obj) {
    if (isset($obj['page'])) {
      $maxPage = max($obj['page'], $maxPage);
    } else {
      echo "<pre>No page set in [", print_r($obj, 1), "]</pre>\n";
    }
  }
  $boardnav_html = renderBoardNav($boardUri, $maxPage, '[Catalog]');

  $tiles_html = '';
  foreach($catalog as $pageNum => $page) {
    foreach($page['threads'] as $thread) {
      $tmp = $tile_template;
      $tmp = str_replace('{{subject}}', htmlspecialchars($thread['sub']),  $tmp);
      $tmp = str_replace('{{message}}', htmlspecialchars($thread['com']),  $tmp);
      $tmp = str_replace('{{name}}',    htmlspecialchars($thread['name']), $tmp);
      $tmp = str_replace('{{no}}',      $thread['no'], $tmp);
      $tmp = str_replace('{{uri}}', $boardUri, $tmp);
      $tmp = str_replace('{{jstime}}', date('c', $thread['created_at']), $tmp);
      $tmp = str_replace('{{human_created_at}}', date('n/j/Y H:i:s', $thread['created_at']), $tmp);
      // FIXME: enable image
      $tile_image = '';
      if (count($thread['files'])) {
        $tile_image = $image_template;
        $tile_image = str_replace('{{uri}}', $boardUri, $tile_image);
        $tile_image = str_replace('{{no}}', $thread['no'], $tile_image);
        $tile_image = str_replace('{{file}}', 'backend/'.$thread['files'][0]['path'], $tile_image);
        /*
        $ftmpl = str_replace('{{filename}}', $file['filename'], $ftmpl);
        $ftmpl = str_replace('{{size}}', $file['size'], $ftmpl);
        $ftmpl = str_replace('{{width}}', $file['w'], $ftmpl);
        $ftmpl = str_replace('{{height}}', $file['h'], $ftmpl);
      */
      }
      $tmp = str_replace('{{tile_image}}', $tile_image, $tmp);
      $tiles_html .= $tmp;
    }
  }
  $boardData = getBoard($boardUri);

  $p = array(
    'boardUri' => $boardUri,
    'tags' => array()
  );
  global $pipelines;
  $pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);
  foreach($p['tags'] as $s => $r) {
    $tmpl = str_replace('{{' . $s . '}}', $r, $tmpl);
  }

  $tmpl = str_replace('{{uri}}',      $boardUri,      $tmpl);
  $tmpl = str_replace('{{description}}', htmlspecialchars($boardData['description']), $tmpl);
  $tmpl = str_replace('{{tiles}}',    $tiles_html,    $tmpl);
  $tmpl = str_replace('{{boardNav}}', $boardnav_html, $tmpl);
  wrapContent($tmpl);
}

function getBoardSettingsHandler($boardUri) {
  global $pipelines;
  $templates = loadTemplates('board_settings');
  $tmpl = $templates['header'];

  $io = array(
    'navItems' => array(),
    'boardUri' => $boardUri,
  );
  $pipelines[PIPELINE_BOARD_SETTING_NAV]->execute($io);
  $nav_html = getNav($io['navItems'], array(
    'uri' => $boardUri,
  ));

  $tmpl = str_replace('{{nav}}', $nav_html, $tmpl);
  //$pipelines['boardSettingTmpl']->execute($tmpl);
  wrapContent($tmpl);
}

?>
