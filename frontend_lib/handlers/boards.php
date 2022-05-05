<?php

// a wiring harness that takes things to the backend
// while providing a bridge from the router
/*
class query_thread {
  function __constructor($request) {
    $this->portals = $request['portals'];
  }
}
// could just be an array...
*/
function request2QueryThread($request) {
  return array(
    'portals' => $request['portals'],
  );
}
// additional functions to automatically handle the header/footer of portals
// probably hooked from the results

// we're a small webpage that's cacheable
function getInlineBoardsLoaderHandler() {
  $row = wrapContentData(array());
  $head_html = wrapContentGetHeadHTML($row);
  // index already puts a header on this based on router config
  global $BASE_HREF;
  echo <<<EOB
<!DOCTYPE html>
<html>
<head id="settings">
  <base href="$BASE_HREF">
  $head_html
</head>
<body id="top">
<a class="nojsonly-block" style="line-height: 100vh; text-align: center; width: 100vw; height: 100vh;" target="boardFrame" href="/boards_inline.html">Please click to load all the html for full board list</a>
EOB;

  // index already did headers
  // nothing more we can set because we're in immediate mode
  /*
  checkCacheHeaders(array(
    'fileSize' => strlen($str), // for etag
    'contentType' => 'text/html',
  ));
  */
}

// refactored out so theme demo can use this
function getBoardThreadListingRender($boardUri, $boardThreads, $pagenum, $wrapOptions = '') {
  $pageData = $boardThreads['page1'];
  $pages = $boardThreads['pageCount'];
  $boardData = $boardThreads['board'];

  $templates = loadTemplates('thread_listing');
  //echo join(',', array_keys($templates));

  // header is used
  // see board_portal
  $page_tmpl = $templates['loop0']; // not used
  $boardnav_html = $templates['loop1']; // stomp (replacement is used in header)
  $file_template = $templates['loop2']; // not used
  $threadHdr_tmpl = $templates['loop3']; // used
  $threadFtr_tmpl = $templates['loop4']; // used
  $thread_tmpl = $templates['loop5']; // not used

  extract(ensureOptions(array(
    'noBoardHeaderTmpl' => false,
  ), $wrapOptions));

  //echo "test[", htmlspecialchars(print_r($templates, 1)),"]<br>\n";

  // FIXME: register/push a portal with wrapContent
  // so it can fast out efficiently
  // also should wrapContent be split into header/footer for efficiency? yes
  // and we need keying too, something like ESI

  // need to set boardSettings here for DEMO
  // but how do we normally get this? boardData['settings']
  // getBoardPortal promotes it internally

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
    //echo "<pre>", print_r($thread, 1), "</pre>\n";
    //echo "[", $thread['posts'][0]['no'], "] replies[", $thread['thread_reply_count'], "]<br>\n";
    if (!isset($thread['posts'])) continue;
    $posts = $thread['posts'];
    $threadId = $posts[0]['no'];
    //echo "count[", count($posts), "]<br>\n";
    $threads_html .= $threadHdr_tmpl;
    // we only include 6...
    //$cnt = count($posts);
    foreach($posts as $i => $post) {
      //if ($i === 0) $threads_html .= $threadHdr_tmpl;
      $topReply = isset($posts[1]) ? $posts[1]['no'] : false;
      $threads_html .= renderPost($boardUri, $post, array(
        'checkable' => true, 'postCount' => $thread['thread_reply_count'],
        'topReply' => $topReply, 'where' => $boardUri . '/'
      ));
      //if ($i === count($posts) - 1) $threads_html .= $threadFtr_tmpl;
    }
    $expander_html = '';
    /*
    // rpp
    if ($thread['thread_reply_count'] > 5) {
      //$lastPost = $posts[count($posts) - 1];
      $threadUrl = '/' . $boardUri . '/thread/' . $threadId . '.html';
      if (isset($posts[1])) {
        $secondPost = $posts[1];
        $threadUrl .= '#' . $secondPost['no'];
      }
      $expander_html = '<a class="expand2Link" target="thread' . $threadId . 'View" href="'. $threadUrl . '" tabindex="0">Expand Thread</a>';
    }
    */
    $threadFtr_tags = array(
      'threadNum' => $threadId,
      // iframe could use loading=lazy but FF doesn't yet support it
      // consider it when it's added
      'expander'  => $expander_html,
    );
    $threads_html .= replace_tags($threadFtr_tmpl, $threadFtr_tags);
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
  // transform req => q
  $q = request2QueryThread($request);
  //echo "<pre>", print_r($request['portals'], 1), "</pre>\n";
  getBoardThreadListing($q, $boardUri);
}

function getBoardThreadListingPageHandler($request) {
  $boardUri = $request['params']['uri'];
  $page = $request['params']['page'] ? $request['params']['page'] : 1;
  $q = request2QueryThread($request);
  getBoardThreadListing($q, $boardUri, $page);
}

function getBoardCatalogHandler($request) {
  $boardUri = $request['params']['uri'];
  renderBoardCatalog($boardUri);
}

function getBlockBypass($boardUri, $row) {
  // regenerate post form...
  // how did $row get threadId = 1
  // FIXME: need a shorthand for files

  $formfields = array(
    'post'   => array('type' => 'hidden'),
    'captcha'    => array('type' => 'captcha', 'label' => 'Bypass CAPTCHA'),
  );
  //

  /*
  $postform = renderPostFormHTML($boardUri, array(
    'reply' => $row['threadId'],
    'formId' => 'bottom_postform',
    'showClose' => false,
    'values' => $row,
  ));
  */
  $values = array('post' => json_encode($row));
  $formOptions = array();
  $bypassForm = generateForm('/bypass', $formfields, $values, $formOptions);

  //echo "<pre>", htmlspecialchars(print_r($postform, 1)), "</pre>\n";
  // 'Thread #' . $row['thread'] . '<br>'. "\n"
  wrapContent('blockBypass was invalid, please try again: <br>' . "\n" . $bypassForm);
}


function retryCaptcha($boardUri, $row) {
  // regenerate post form...
  // how did $row get threadId = 1
  // FIXME: need a shorthand for files
  $postform = renderPostFormHTML($boardUri, array(
    'reply' => $row['threadId'],
    'formId' => 'bottom_postform',
    'showClose' => false,
    'values' => $row,
  ));
  //echo "<pre>", htmlspecialchars(print_r($postform, 1)), "</pre>\n";
  // 'Thread #' . $row['thread'] . '<br>'. "\n"
  wrapContent('Your Captcha was invalid, please try again: <br>' . "\n" . $postform);
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
  $headers = array(
    // was HTTP_X_FORWARDED_FOR
    // but this is the actual header on the wire...
    'x-forwarded-for' => getip(),
    'sid' => getCookie('session'),
  );
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
  // has thread
  if (!empty($_POST['thread'])) {
    // make a reply
    $row['threadId'] = $_POST['thread'];
    $endpoint = 'lynx/replyThread';
    $redir .= 'thread/' . $_POST['thread'];
  }
  if (!empty($_POST['files_already_uploaded'])) {
    $already = json_decode($_POST['files_already_uploaded'], true);
    if (!is_array($already)) {
      echo "boards::makePostHanlder - Can't decode[", htmlspecialchars($_POST['files_already_uploaded']), "]<br>\n";
      $already = array();
    }
    $files = json_decode($row['files']);
    if (!is_array($files)) $files = array();
    // don't do anything about duplicates
    // you could make patterns...
    $row['files'] = json_encode(array_merge($already, $files));
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
    //print_r($io);
    // FIXME: clean this up better
    if ($io['error'] === 'CAPTCHA is required') {
      retryCaptcha($boardUri, $row);
      return;
    }
    echo "error";
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
    // invalid json
    wrapContent('Post Error: <pre>' . $json . '</pre>');
  } else {
    //echo "<pre>", $endpoint, '[', print_r($result, 1), "]</pre>\n";
    //echo "redir[$redir]<br>\n";
    //return;
    if ($result && is_array($result) && isset($result['data']) && is_numeric($result['data'])) {
      // success
      redirectTo($redir);
    } else
    if ($result && is_array($result) && isset($result['data']) && is_array($result['data']) && $result['data']['status'] === 'queued') {
      // success (queued)
      redirectTo($redir);
    } else {
      // valid json
      if ($result['data'] === 'Expired captcha.' || $result['data'] === 'Wrong captcha.') {
        //print_r($row);
        retryCaptcha($boardUri, $row);
      } else
      if ($result['data']['status'] === 'bypassable') {
        //wrapContent('Block Bypass Expired');
        getBlockBypass($boardUri, $row);
      } else
      if ($result['data'] === 'Thread not found.') {
        wrapContent('Thread ' . $_POST['thread'] . ' not found' . "\n");
      } else {
        wrapContent('Post Error: ' . print_r($result, 1));
      }
    }
  }
}

// /:uri/
function getBoardThreadListing($q, $boardUri, $pagenum = 1) {
  //echo "pagenum[$pagenum]<br>\n";

  // this does the backend call
  // if we know what portal we're using
  // well ofc we fucking do...
  // but yea, we could pass an option to do something?
  // pass a parameter to the backend that says baord portal
  // and then things can hook on it
  $boardThreads = backendGetBoardThreadListing($q, $boardUri, $pagenum);
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
        //echo "page[$pageNum]<br>\n";
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
          // starts at 0
          'page' => $pageNum + 1,
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

?>