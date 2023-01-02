<?php

// this data and functions used for all frontend module php code

// function are automatically exported

/*
function backendGetBoardThreadListing($q, $boardUri, $pageNum = 1) {
  $options = array(
    'endpoint'    => 'opt/boards/' . $boardUri . '/' . $pageNum,
    'querystring' => array('portals' => getPortalsToUrl($q)),
    'expectJson'  => true,
    'sendSession' => true, // expectJson sends this
    // sorta fakes it enough to check with BE
    'cacheSettings' => true, // mark cacheable
  );
  // cacheable
  $threadListing = consume_beRsrc($options);
  //echo "<pre>lib.backend::backendGetBoardThreadListing -  ", print_r($threadListing, 1), "</pre>\n";
  //$threadListing = getExpectJson(addPortalsToUrl($q, 'opt/boards/' . $boardUri . '/' . $pageNum));
  //echo "type[", gettype($threadListing), "][$threadListing]\n";
  if (!$threadListing) return;
  if (isset($threadListing['data']['board']['settings'])) {
    global $board_settings;
    $board_settings = $threadListing['data']['board']['settings'];
  }
  return $threadListing['data'];
}
*/

// /:uri/
function getBoardThreadListing($q, $boardUri, $pagenum = 1) {
  //echo "pagenum[$pagenum]<br>\n";

  // this does the backend call
  // if we know what portal we're using
  // well ofc we fucking do...
  // but yea, we could pass an option to do something?
  // pass a parameter to the backend that says baord portal
  // and then things can hook on it
  //$boardThreads = backendGetBoardThreadListing($q, $boardUri, $pagenum);
  //global $pkg;
  global $packages;
  // will this upload $threadListing['data']['board']['settings']
  // probably
  // also we know the portals because of the nature of the groupdata and where this file is
  $boardThreads = $packages['base_board_view']->useResource('board_page', array('uri' => $boardUri, 'page' => $pagenum, 'portals' => join(',', $q['portals'])));
  if (!$boardThreads) {
    wrapContent("There is a problem with the backend [$boardUri]");
    return;
  }
  // lynxbridge
  if (empty($boardThreads['pageCount'])) {
    http_response_code(404);
    wrapContent("Board [$boardUri] does not exist");
    return;
  }
  //echo "<pre>", print_r($boardThreads, 1), "</pre>\n";

  getBoardThreadListingRender($boardUri, $boardThreads, $pagenum);
}

// refactored out so theme demo can use this
function getBoardThreadListingRender($boardUri, $boardThreads, $pagenum, $wrapOptions = '') {
  $pageData = $boardThreads['page1'];
  $pages = $boardThreads['pageCount'];
  $boardData = $boardThreads['board'];

  // FIXME: loadModuelTemplates
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
    'noActions' => false,
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
  // hack for now
  $userSettings = getUserSettings();
  //echo "<pre>userSettings:", print_r($userSettings, 1), "</pre>\n";
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
        'checkable' => true, 'postCount' => empty($thread['thread_reply_count']) ? -1 : $thread['thread_reply_count'],
        'topReply' => $topReply, 'where' => $boardUri . '/', 'boardSettings' => $boardData['settings'],
        'userSettings' => $userSettings, 'noActions' => $noActions,
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
      'postactions' => $noActions ? '' : renderPostActions($boardUri),
    ),
  );
  $pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);
  $tmpl = replace_tags($templates['header'], $p['tags']);
  wrapContent($boardPortal['header'] . $tmpl . $boardPortal['footer'], $wrapOptions);
}

// allow export of data as $common in your handlers and modules
return array(
);

?>