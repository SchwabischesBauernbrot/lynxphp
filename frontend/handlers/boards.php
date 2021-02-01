<?php

function secondsToTime($inputSeconds) {
  global $now;

  $obj = new DateTime();
  $obj->setTimeStamp($now - $inputSeconds);

  $diff = $then->diff(new DateTime(date('Y-m-d H:i:s', $now)));
  return array('years' => $diff->y, 'months' => $diff->m, 'days' => $diff->d, 'hours' => $diff->h, 'minutes' => $diff->i, 'seconds' => $diff->s);
}

function relativeColor($relativeTo) {
  global $now;
  $SECOND = 1;
  $MINUTE = 60; // 60s in 1min
  $HOUR   = 3600;
  $DAY    = 86400;
  $WEEK   = 604800;
  $MONTH  = 2629800;
  $YEAR   = 31536000;

  $diff = $now - $relativeTo;
  $minAgo = floor($diff / $MINUTE);

  $r=0; $g=0; $b=0;
  if ($diff < $MINUTE) {
    $g = 0.7; $b = 1;
  } else
  if ($diff < $HOUR) {
    $r = ($minAgo / 60) * 0.5;
    $g = 1;
  } else
  if ($diff < $DAY) {
    $r = 0.5 + ($minAgo / 1440) * 0.5;
    $g = 1;
  } else
  if ($diff < $WEEK) {
    $g = 1 - ($minAgo /10080) * 0.5;
    $r = 1;
  } else
  if ($diff < $MONTH) {
    $g = 0.5 - ($minAgo / 43830) * 0.5;
    $r = 1;
  } else
  if ($diff < $YEAR) {
    $r = 1 - ($minAgo / 525960);
  }
  // else leave it black

  return sprintf('%02x%02x%02x', $r * 255, $g * 255, $b * 255);
}

function getBoardsHandler() {
  global $now;
  $res = getBoards();
  $boards = $res['data'];

  $templates = loadTemplates('board_listing');
  $overboard_template = $templates['loop0'];
  $board_template     = $templates['loop1'];
  $page_template      = $templates['loop2'];

  $boards_html = '';
  foreach($boards as $c=>$b) {
    $last = '';
    $color = ''; // green
    if (!empty($b['last'])) {
      $time = $now - $b['last']['updated_at'];

      $months = floor($time / (60 * 60 * 24 * 30));
      $time -= $months * (60 * 60 * 24 * 30);

      $weeks = floor($time / (60 * 60 * 24 * 7));
      $time -= $weeks * (60 * 60 * 24 * 7);

      $days = floor($time / (60 * 60 * 24));
      $time -= $days * (60 * 60 * 24);

      $hours = floor($time / (60 * 60));
      $time -= $hours * (60 * 60);

      $minutes = floor($time / 60);
      $time -= $minutes * 60;

      $seconds = floor($time);
      $time -= $seconds;

      $last = '';
      if ($seconds) {
        $last = $seconds . ' seconds ago';
      }
      if ($minutes) {
        $last = $minutes    . ' minute ago';
      }
      if ($hours) {
        $last = $hours   . ' hour(s) ago';
        $color = '7cd900';
      }
      if ($days) {
        $last = $days    . ' day(s) ago';
        $color = 'd9b900'; // yellow
      }
      if ($weeks) {
        $last = $weeks   . ' week(s) ago';
        $color = 'd95200'; // orange
      }
      if ($months) {
        $last = $months  . ' month(s) ago';
        $color = 'c50000'; // red
      }

      $color = relativeColor($b['last']['updated_at']);
    }

    $tmp = $board_template;
    $tmp = str_replace('{{uri}}', $b['uri'], $tmp);
    $tmp = str_replace('{{title}}', htmlspecialchars($b['title']), $tmp);
    $tmp = str_replace('{{description}}', htmlspecialchars($b['description']), $tmp);
    $tmp = str_replace('{{threads}}', $b['threads'], $tmp);
    $tmp = str_replace('{{posts}}', $b['posts'], $tmp);
    $tmp = str_replace('{{lastActivityColor}}', $color, $tmp);
    $tmp = str_replace('{{last_post}}', $last, $tmp);
    $boards_html .= $tmp . "\n";
  }

  $content = $templates['header'];
  $content = str_replace('{{overboard}}', '', $content);
  $content = str_replace('{{fields}}', '', $content);

  $page_html = '';
  $tmp = $page_template;
  $tmp = str_replace('{{page}}', 1, $tmp);
  $page_html .= $tmp;

  $content = str_replace('{{pages}}',  $page_html, $content);
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
  if (!$boardThreads) {
    wrapContent("There is a problem with the backend");
    return;
  }
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
      $threads_html .= renderPost($boardUri, $post, array('checkable' => true));
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
  // mixin
  $tmpl = str_replace('{{postform}}', renderPostForm($boardUri, $boardUri . '/'), $tmpl);
  $tmpl = str_replace('{{postactions}}', renderPostActions($boardUri), $tmpl);

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

  $boardData = getBoardThread($boardUri, $threadNum);
  $posts_html = '';
  $files = 0;
  foreach($boardData['posts'] as $post) {
    //echo "<pre>", print_r($post, 1), "</pre>\n";
    $tmp = $post_template;
    $posts_html .= renderPost($boardUri, $post, array('checkable' => true));
    $files += count($post['files']);
  }

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

  $tmpl = str_replace('{{replies}}', count($boardData['posts']) - 1, $tmpl);
  $tmpl = str_replace('{{files}}', $files, $tmpl);

  // mixins
  $tmpl = str_replace('{{postform}}', renderPostForm($boardUri, $boardUri . '/thread/' . $threadNum . '.html', array('reply' => $threadNum)), $tmpl);
  $tmpl = str_replace('{{postactions}}', renderPostActions($boardUri), $tmpl);
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
      $tmp = str_replace('{{replies}}', $thread['reply_count'], $tmp);
      $tmp = str_replace('{{files}}', $thread['file_count'], $tmp);
      $tmp = str_replace('{{page}}', $page['page'], $tmp);
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
  // mixin
  $tmpl = str_replace('{{postform}}', renderPostForm($boardUri, $boardUri . '/catalog'), $tmpl);
  $tmpl = str_replace('{{postactions}}', renderPostActions($boardUri), $tmpl);
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
