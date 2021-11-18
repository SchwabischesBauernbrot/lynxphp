<?php

function secondsToTime($inputSeconds) {
  global $now;

  $obj = new DateTime();
  $obj->setTimeStamp($now - $inputSeconds);

  $diff = $then->diff(new DateTime(gmdate('Y-m-d H:i:s', $now)));
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

  $pageNum = 1;
  $params = array(
    'search' => '',
    'sort' => 'activity',
    'direction' => 'desc',
  );
  // popularity desc is the default
  // popularity desc should be highest post at the top
  // prettier if Latest activity is the default
  if (!empty($_REQUEST['search'])) {
    $params['search'] = $_REQUEST['search'];
  }
  if (!empty($_REQUEST['sort'])) {
    $params['sort'] = $_REQUEST['sort'];
  }
  $reverse_list = true;
  if (!empty($_REQUEST['direction'])) {
    //$params['direction'] = $_GET['direction'];
    $reverse_list = $_REQUEST['direction'] !== 'asc';
  }
  if (!empty($_GET['page'])) {
    $pageNum = (int)$_GET['page'];
  }
  $params['page'] = $pageNum;
  $params['direction'] = $reverse_list ? 'desc' : 'asc';

  //print_r($params);
  $res = getBoards($params);
  $boards = $res['data']['boards'];
  // FIXME: not very cacheable like this...
  $settings = $res['data']['settings'];
  /*
  if (BACKEND_TYPE === 'default') {
    if ($reverse_list) {
    }
  }
  */
  $boards = array_reverse($boards);

  $templates = loadTemplates('board_listing');
  $overboard_template = $templates['loop0'];
  $board_template     = $templates['loop1'];
  $page_template     = $templates['loop2'];
  //echo "<pre>pages_template", htmlspecialchars(print_r($page_template, 1)), "</pre>\n";

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

  $content = str_replace('{{search}}', $params['search'], $content);
  $content = str_replace('{{popularitySelected}}', $params['sort'] === 'popularity' ? ' selected' : '', $content);
  $content = str_replace('{{latestSelected}}', $params['sort'] === 'activity' ? ' selected' : '', $content);

  $content = str_replace('{{descSelected}}', $reverse_list ? ' selected' : '', $content);
  $content = str_replace('{{ascSelected}}', $reverse_list ? '' : ' selected', $content);


  $page_html = '';
  if (isset($res['data']['pageCount'])) {
    //print_r($params);
    $qParams = array();
    if ($params['search']) $qParams['search'] = $params['search'];
    if ($params['sort'] !== 'activity') $qParams['sort'] = $params['sort'];
    if ($params['direction'] !== 'desc') $qParams['direction'] = 'asc';
    $qs = paramsToQuerystringGroups($qParams);
    for($i = 0; $i < $res['data']['pageCount']; $i++) {
      $tmp = $page_template;
      // we lose dir and sort
      $tmp = str_replace('{{page}}', ($i + 1), $tmp);
      $tmp = str_replace('{{qs}}', 'page=' . ($i + 1) . '&' . join('&', $qs), $tmp);
      $tmp = str_replace('{{bold}}', $pageNum == $i + 1 ? 'bold' : '', $tmp);
      $page_html .= $tmp;
    }
  } else {
    $tmp = $page_template;
    $tmp = str_replace('{{page}}', 1, $tmp);
    $tmp = str_replace('{{bold}}', 'bold', $tmp);
    $page_html .= $tmp;
  }

  $content = str_replace('{{pages}}',  $page_html, $content);
  $content = str_replace('{{boards}}', $boards_html, $content);
  // FIXME get named route
  global $BASE_HREF;
  $content = str_replace('{{action}}', $BASE_HREF . 'boards.php', $content);

  wrapContent($content, array('settings' => $settings));
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

// probably shoudl be moved into a lib or inlined...
function preprocessPost(&$p) {
  global $pipelines;
  $pipelines[PIPELINE_POST_PREPROCESS]->execute($p);
}

// refactored out so theme demo can use this
function getBoardThreadListingRender($boardUri, $boardThreads, $pagenum, $wrapOptions = '') {
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

  extract(ensureOptions(array(
    'noBoardHeaderTmpl' => false,
  ), $wrapOptions));

  //echo "test[", htmlspecialchars(print_r($templates, 1)),"]<br>\n";

  // FIXME: register/push a portal with wrapContent
  // so it can fast out efficiently
  // also should wrapContent be split into header/footer for efficiency? yes
  // and we need keying too, something like ESI
  $boardData['pageCount'] = $boardThreads['pageCount'];
  $boardPortal = getBoardPortal($boardUri, $boardData, array(
    'pagenum' => $pagenum, 'noBoardHeaderTmpl' => $noBoardHeaderTmpl));
  $boardnav_html = '';

  // used to look at text, so we can queue up another backend query if needed
  // FIXME: check count of PIPELINE_POST_PREPROCESS
  $nPosts = array();
  foreach($pageData as $i => $thread) {
    if (!isset($thread['posts'])) continue;
    $posts = $thread['posts'];
    foreach($posts as $j => $post) {
      preprocessPost($pageData[$i]['posts'][$j]);
      $nPosts[] = $post;
    }
  }
  global $pipelines;
  $data = array(
    'posts' => $nPosts,
    'boardThreads' => $boardThreads,
    'pagenum' => $pagenum
  );
  $pipelines[PIPELINE_POST_POSTPREPROCESS]->execute($data);
  unset($nPosts);

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

  $p = array(
    'boardUri' => $boardUri,
    'tags' => array(
      // need this for form actions
      'uri' => $boardUri,
      // title, description?
      //'title' => htmlspecialchars($boardData['title']),
      //'description' => htmlspecialchars($boardData['description']),
      'threads' => $threads_html,
      'boardNav' => $boardnav_html,
      'pagenum' => $pagenum,
      // mixin
      //'postform' => renderPostForm($boardUri, $boardUri . '/catalog'),
      'postactions' => renderPostActions($boardUri),
    ),
  );
  $pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);
  $tmpl = replace_tags($templates['header'], $p['tags']);
  wrapContent($boardPortal['header'] . $tmpl . $boardPortal['footer'], $wrapOptions);
}

// /:uri
function getBoardFileRedirect($request) {
  $boardUri = $request['params']['uri'];
  if ($boardUri) {
    $boardUri .= '/';
  }
  // FIXME: only redir if the board exists...
  global $BASE_HREF;
  redirectTo($BASE_HREF . $boardUri);
  //echo "Would redirect to [$boardUri]\n";
}

function getBoardThreadListingHandler($request) {
  $boardUri = $request['params']['uri'];
  getBoardThreadListing($boardUri);
}

function getBoardThreadListingPageHandler($request) {
  $boardUri = $request['params']['uri'];
  $page = $request['params']['page'] ? $request['params']['page'] : 1;
  getBoardThreadListing($boardUri, $page);
}

function getBoardCatalogHandler($request) {
  $boardUri = $request['params']['uri'];
  renderBoardCatalog($boardUri);
}

function getBoardSettingsHandler($request) {
  $boardUri = $request['params']['uri'];
  getBoardSettings($boardUri);
}

function makePostHandler($request) {
  global $pipelines, $max_length;
  $boardUri = $request['params']['uri'];

  //echo '<pre>_POST: ', print_r($_POST, 1), "</pre>\n";
  //echo "max_length[$max_length]<br>\n";
  //echo '<pre>_SERVER: ', print_r($_SERVER, 1), "</pre>\n";
  //echo '<pre>_FILES: ', print_r($_FILES, 1), "</pre>\n";

  $res = processFiles();
  //echo '<pre>res: ', print_r($res, 1), "</pre>\n";
  $files = isset($res['handles']['files']) ? $res['handles']['files'] : array();
  //echo '<pre>files: ', print_r($files, 1), "</pre>\n";

  $endpoint = 'lynx/newThread';
  global $BASE_HREF;
  $redir = $BASE_HREF . $boardUri . '/';
  $headers = array('HTTP_X_FORWARDED_FOR' => getip(), 'sid' => getCookie('session'));
  $row = array(
    // noFlag
    'name'     => getOptionalPostField('name'),
    'email'    => getOptionalPostField('email'),
    'message'  => getOptionalPostField('message'),
    'subject'  => getOptionalPostField('subject'),
    'boardUri' => $boardUri,
    'password' => getOptionalPostField('postpassword'),
    // captcha
    'spoiler'  => empty($_POST['spoiler_all']) ? '' : $_POST['spoiler_all'],
    'files'    => json_encode($files),
    // flag
  );
  if (!empty($_POST['thread'])) {
    $row['threadId'] = $_POST['thread'];
    $endpoint = 'lynx/replyThread';
    $redir .= 'thread/' . $_POST['thread'];
  }
  $io = array(
    'boardUri' => $boardUri,
    'endpoint' => $endpoint,
    'headers'  => $headers,
    'values'   => $row,
    'redir'    => $redir,
    'error'    => false,
    'redirNow' => false,
  );
  // validate results
  $pipelines[PIPELINE_POST_VALIDATION]->execute($io);
  //print_r($io);
  $row     = $io['values'];
  $headers = $io['headers'];
  $redir   = $io['redir'];
  if (!empty($io['error'])) {
    echo "error";
    //print_r($io);
    wrapContent($io['error']);
    return;
  }
  if (!empty($io['redirNow'])) {
    echo "redirNow";
    redirectTo($io['redirNow']);
    return;
  }

  // make post...
  $json = curlHelper(BACKEND_BASE_URL . $endpoint, $row, $headers);
  // can't use this because we need better handling of results...
  //$result = expectJson($json, $endpoint)
  //echo "json[$json]<br>\n";
  $result = json_decode($json, true);
  if ($result === false) {
    wrapContent('Post Error: <pre>' . $json . '</pre>');
  } else {
    //echo "<pre>", $endpoint, print_r($result, 1), "</pre>\n";
    //echo "redir[$redir]<br>\n";
    //return;
    if ($result && is_array($result) && isset($result['data']) && is_numeric($result['data'])) {
      // success
      redirectTo($redir);
    } else {
      wrapContent('Post Error: ' . print_r($result, 1));
    }
  }
}

// /:uri/
function getBoardThreadListing($boardUri, $pagenum = 1) {
  //echo "pagenum[$pagenum]<br>\n";
  $boardThreads = backendGetBoardThreadListing($boardUri, $pagenum);
  if (!$boardThreads) {
    wrapContent("There is a problem with the backend [$boardUri]");
    return;
  }
  //echo "<pre>", print_r($boardThreads, 1), "</pre>\n";

  getBoardThreadListingRender($boardUri, $boardThreads, $pagenum);
}

function renderBoardCatalog($boardUri) {
  $data = getBoardCatalog($boardUri);
  $catalog = $data['pages'];
  $boardData = $data['board'];
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
  $posts = array();
  if (is_array($catalog)) {
    foreach($catalog as $i=>$obj) {
      if (isset($obj['page'])) {
        $maxPage = max($obj['page'], $maxPage);
      } else {
        echo "<pre>No page set in [", print_r($obj, 1), "]</pre>\n";
      }
      foreach($obj['threads'] as $j => $post) {
        preprocessPost($catalog[$i]['threads'][$j]);
        $posts[] = $post;
      }
    }
  }

  global $pipelines;
  $data = array(
    'posts'    => $posts,
    'catalog'  => $catalog,
    'boardUri' => $boardUri,
  );
  $pipelines[PIPELINE_POST_POSTPREPROCESS]->execute($data);
  unset($posts);

  //$boardnav_html = renderBoardNav($boardUri, $maxPage, '[Catalog]');
  $boardnav_html = '';

  $tiles_html = '';
  if (is_array($catalog)) {
    global $BASE_HREF;
    $tile_tags = array('uri' => $boardUri);
    foreach($catalog as $pageNum => $page) {
      foreach($page['threads'] as $thread) {
        /*
        $tile_image = '<a href="' . BASE_HREF . $boardUri . '/thread/' .
          $thread['no']. '.html#' . $thread['no'] .
          '"><img src="images/imagelessthread.png" width=209 height=64></a><br>';
        */
        //echo "<pre>thread[", print_r($thread, 1), "]</pre>\n";

        // update thread number
        $tile_tags['no'] = $thread['no'];
        //$tile_image = str_replace('{{file}}', 'backend/' . $thread['files'][0]['path'], $tile_image);
        // filename, size, w, h
        // thumb to be set
        if (isset($thread['files']) && count($thread['files'])) {
          $tile_tags['thumb'] = getThumbnail($thread['files'][0], array('maxW' => 209));
        } else {
          $tile_tags['thumb'] = '<img src="images/imagelessthread.png" width=209 height=64>';
        }
        // need $BASE_HREF..
        // do we? we have it in the base tag...
        $tags = array(
          'uri' => $boardUri,
          'subject' => htmlspecialchars($thread['sub']),
          'message' => htmlspecialchars($thread['com']),
          'name' => htmlspecialchars($thread['name']),
          'no' => $thread['no'],
          'jstime' => gmdate('Y-m-d', $thread['created_at']) . 'T' . gmdate('H:i:s.v', $thread['created_at']) . 'Z',
          'human_created_at' => gmdate('n/j/Y H:i:s', $thread['created_at']),
          'replies' => $thread['reply_count'],
          'files' => $thread['file_count'],
          'page' => $pageNum,
          'tile_image' => replace_tags($image_template, $tile_tags),
        );
        $tiles_html .= replace_tags($tile_template, $tags);
      }
    }
  }
  //$boardData = getBoard($boardUri);
  //$boardData['pageCount'] = $boardThreads['pageCount'];
  $boardData['pageCount'] = $maxPage;
  // but no footer...
  $boardHeader = renderBoardPortalHeader($boardUri, $boardData, array(
    'isCatalog' => true,
  ));

  $p = array(
    'boardUri' => $boardUri,
    'tags' => array(
      'uri' => $boardUri,
      'description' => htmlspecialchars($boardData['description']),
      'tiles' => $tiles_html,
      'boardNav' => $boardnav_html,
      // mixin
      //'postform' => renderPostForm($boardUri, $boardUri . '/catalog'),
      'postactions' => renderPostActions($boardUri),
    ),
  );
  global $pipelines;
  $pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);
  $tmpl = replace_tags($tmpl, $p['tags']);
  wrapContent($boardHeader . $tmpl);
}

function getBoardSettings($boardUri) {
  global $pipelines;
  $templates = loadTemplates('board_settings');
  $tmpl = $templates['header'];

  $io = array(
    'navItems' => array(),
    'boardUri' => $boardUri,
  );
  $pipelines[PIPELINE_BOARD_SETTING_NAV]->execute($io);
  $nav_html = getNav($io['navItems'], array(
    'replaces' => array('uri' => $boardUri),
  ));

  $tmpl = str_replace('{{nav}}', $nav_html, $tmpl);
  //$pipelines['boardSettingTmpl']->execute($tmpl);
  wrapContent($tmpl);
}

?>