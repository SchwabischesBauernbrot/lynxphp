<?php

global $portalsConfig;
$portalsConfig['posts'] = array(
  // 'uri' => array('type' => 'params', 'name' => 'uri')
  'params' => array()
);

// a handler(router) adapter
function getPortalPosts($opts, $request) {
  global $portalData;
  //echo "<pre>getPortalPosts opts[", print_r($opts, 1), "]</pre>\n";
  //echo "<pre>getPortalPosts request[", print_r($request, 1), "]</pre>\n";
  // supposed to be here but the last call is stopming this...
  //echo "<pre>getPortalPosts portalData[", print_r($portalData, 1), "]</pre>\n";
  //echo "<pre>getPortalPosts paramsCode[", print_r($opts['paramsCode'], 1), "]</pre>\n";
  //$config = getPortalBoardConfig();
  //global $portalsConfig;
  // meta coding
  $params = portal_getParamsFromContext($opts['paramsCode'], $request);
  //echo "<pre>getPortalPosts params[", print_r($params, 1), "]</pre>\n";

  //global $boardData;
  //echo "<pre>getPortalPosts boardData[", print_r($boardData, 1), "]</pre>\n";

  // boardData['pageCount']
  $options = array(
    'board' => '',
    //'pagenum' => empty($params['page']) ? 1 : $params['page'],
    'threadNum' => empty($params['num']) ? 0 : $params['num'],
    'noBoardHeaderTmpl' => empty($opts['noBoardHeaderTmpl']) ? false : true,
    'isThread' => empty($opts['isThread']) ? false : true,
    'threadClosed' => empty($opts['threadClosed']) ? false : true,
    // FIXME: this should default to on tbh
    'noPosts' => empty($opts['noPosts']) ? false : true,
  );
  // get page count
  // so on board_view this comes from a specific custom endpoint
  // maybe better solved by a portal linkage...
  // implying moving portals into common
  // so we can couple BE and FE coordination together
  // so header/footer can rely on data coming from BE EPs
  // should be similar to a BE handler
  // so if the page has no BE calls, we can just get what we need
  global $_portalData;
  //echo "<pre>posts::_portalData", print_r($_portalData['posts'], 1), "</pre>\n";

  //echo "threadNum[", $options['threadNum'], "]<br>\n";

  //$pageCount = 0;
  if (isset($_portalData['posts']['pageCount'])) {
    //$pageCount = $_portalData['posts']['pageCount'];
    $uri = $params['uri'];
  } // else how? which EP
  else {
    if (!empty($opts['uri'])) {
      //$pageCount = $opts['pageCount'];
      $uri = $opts['uri'];
    } else {
      //global $packages;
      // without portals=board we're missing the banner data
      // this hangs shit...
      // , 'portals' => 'boards'
      // should already auto-matically add it... right?
      /*
      $boardThreads = $packages['base_board_view']->useResource('board_page', array('uri' => $params['uri'], 'page' => $options['pagenum']));
      //echo "<pre>boardThreads", print_r($boardThreads, 1), "</pre>\n";
      global $boardData;
      if (!$boardData) {
        $boardData = $boardThreads['board'];
      }
      */
      //echo "<pre>boardThreads", print_r($boardThreads, 1), "</pre>\n";
      //$pageCount = $boardThreads['pageCount'];
      $uri = $params['uri'];
    }
  }
  // FIXME: pipelinable
  if ($uri !== 'overboard') {
    $boardSettings = getter_getBoardSettings($uri);
  } else {
    $boardSettings = array();
  }
  $row = renderPostsPortalData($uri, array(
    'noBoardHeaderTmpl' => $options['noBoardHeaderTmpl'],
    'isThread' => $options['isThread'],
    'threadNum' => $options['threadNum'],
    'threadClosed' => $options['threadClosed'],
    'boardSettings' => $boardSettings,
    'noPosts' => $options['noPosts'],
    'threadNum' => empty($params['num']) ? 0 : $params['num'],
  ));
  if (!empty($portalData['posts']['threadPostCnt'])) {
    $row['postCount'] = $portalData['posts']['threadPostCnt'];
  }
  if (isset($portalData['posts']['threadFileCnt'])) {
    $row['fileCount'] = $portalData['posts']['threadFileCnt'];
  }
  return array(
    'uri' => $uri,
    'portalSettings' => $row,
    //'noPosts' => $options['noPosts'],
  );
}

// refactored out so we can share data between header/footer
// without having to recalculate it
function renderPostsPortalData($boardUri, $options = false) {
  global $pipelines;

  extract(ensureOptions(array(
    'pagenum'   => 0,
    'isCatalog' => false,
    // isn't isThread and ThreadNum the samething?
    'isThread'  => false,
    'noPosts'   => false,
    'threadNum' => 0,
    'noBoardHeaderTmpl' => false,
    // turns off post_form:
    'threadClosed'      => false,
    'threadSaged'       => false,
    'maxMessageLength'  => false,
    'boardSettings'     => false,
  ), $options));

  $templates = loadTemplates('mixins/posts_header');
  $tmpl = $templates['header'];

  // would be nice to have the board settings by here
  // so we can pass it in to control/hint the nav
  // we need boardSettings for pipelines (mainly nav)
  if ($boardSettings === false) {
    if (DEV_MODE) {
      echo "No boardSettings passed to renderPostsPortalData<Br>\n";
    }
    $boardSettings = getter_getBoardSettings($boardUri);
  }

  //echo "<pre>boardNav[", htmlspecialchars(print_r($boardNav, 1)), "]</pre>\n";

  $p = array(
    'tags' => array(
      //'board_header_top' => generateJsBoardInfo($boardUri, $boardSettings),
      //'board_header_bottom' => '',
    ),
    'boardUri' => $boardUri,
  );
  //echo "noBoardHeaderTmpl[$noBoardHeaderTmpl]<Br>\n";
  if (!$noBoardHeaderTmpl) {
    // banner is injected here:
    //$pipelines[PIPELINE_BOARD_HEADER_TMPL]->execute($p);
  }

  // if threadNum, is it locked?
  $form_html = '';
  if (!$noPosts) {
    $form_html = $threadClosed ? '' : renderPostFormHTML($boardUri, array(
      'showClose' => false, 'formId' => 'bottom_postform',
      'reply' => $threadNum, 'maxMessageLength' => $maxMessageLength,
    ));
  }

  return array(
    'tmpl' => $tmpl,
    'tags' => $p['tags'],
    'isCatalog' => $isCatalog,
    'threadNum' => $threadNum,
    'pagenum' => $pagenum,
    'threadClosed' => $threadClosed,
    'threadSaged'  => $threadSaged,
    // used in footer
    //'boardNav' => $boardNav,
    'postForm' => $form_html,
    'noPosts'  => $noPosts,
    'maxMessageLength' => $maxMessageLength,
  );
}

function getPortalPostsHeader($data) {
  //global $boardData; // better than nothing
  if ($data['uri'] === 'overboard') {
    $boardData = array(
      'pageCount' => 1,
      'title' => 'All Boards',
      'description' => 'posts across the site',
      'settings' => array(),
    );
  } else {
    $boardData = getter_getBoard($data['uri']);
  }
  echo renderPostsPortalHeaderEngine($data['portalSettings'], $data['uri'], $boardData);
}

function renderPostsPortalHeaderEngine($row, $boardUri, $boardData) {
  global $pipelines;
  $isCatalog = $row['isCatalog'];
  $threadNum = $row['threadNum'];
  $pagenum   = $row['pagenum'];
  //$tmpl      = $row['tmpl'];

  $renderPostFormOptions = array(
    'maxMessageLength' => $row['maxMessageLength'],
  );
  //$renderPostFormUrl = $boardUri . '/';
  if ($threadNum) {
    $renderPostFormOptions['reply'] = $threadNum;
    //$renderPostFormUrl .= 'thread/' . $threadNum . '.html';
  } else
  if ($pagenum) {
    // why is page important?
    // new threads appear on page one...
    // and you can't make a reply from the listing...
    $renderPostFormOptions['page'] = $pagenum;
    //$renderPostFormUrl .= 'page/' . $pagenum;
  }
  $renderPostFormUrl = $_SERVER['REQUEST_URI'];

  $stickNav_html = '';
  $pipelines[PIPELINE_BOARD_STICKY_NAV]->execute($stickNav_html);

  if (!isset($boardData['title'])) $boardData['title'] = 'Communication problem';
  if (!isset($boardData['description'])) $boardData['description'] = 'try again in a bit';

  return replace_tags($row['tmpl'], array_merge($row['tags'], array(
    'uri' => $boardUri,
    'url' => $_SERVER['REQUEST_URI'],
    'title' => $isCatalog ? '' : ' - ' . htmlspecialchars($boardData['title']),
    'description' => htmlspecialchars($boardData['description']),
    // if postForm is set, the thread is not closed
    'postform' => $row['postForm'] ? renderPostForm($boardUri, $renderPostFormUrl, $renderPostFormOptions) : '',
    'sticknav' => $stickNav_html,
    //'boardNav' => $row['boardNav'],
    'pretitle' => $isCatalog ? 'Catalog(' : '',
    'posttitle' => $isCatalog ? ')' : '',
    'linkStyle' => $isCatalog ? '' : ' style="color: var(--board-title)"',
    'vichanBanner' => $threadNum ? '' : ' style="display: none"',
    // Closed > Saged > Reply
    'mode' => $row['threadClosed'] ? 'Closed' : ($row['threadSaged'] ? 'Saged' : 'Reply'),
  )));
}

function getPortalPostsFooter($data) {
  //global $boardData; // better than nothing
  $boardData = getter_getBoard($data['uri']);
  echo renderPostsPortalFooterEngine($data['portalSettings'], $data['uri'], $boardData);
}

function renderPostsPortalFooterEngine($row, $boardUri, $boardData) {
  global $pipelines;
  //echo "<pre>row[", htmlspecialchars(print_r($row, 1)), "]</pre>\n";
  //echo "<pre>boardData[", htmlspecialchars(print_r($boardData, 1)), "]</pre>\n";

  $templates = loadTemplates('mixins/posts_footer');
  $tmpl = $templates['header'];
  $threadstats_tmpl = $templates['loop0'];
  $postForm_tmpl = $templates['loop1'];
  $liveText_tmpl = $templates['loop2'];

  $enabler_html = '';
  if (!$row['threadNum']) {
    // turn it off, why?
    $enabler_html = '<style>
#autoRefreshEnable {
  display: none;
}
</style>';
  }

  $threadstats_html = '';
  // honestly, this probably doesn't belong here
  // it's only useful on the thread view page
  // and we can inline it at the top...
  // maybe can stay here with an option to pass the data...
  // the data doesn't belong in the be portal data
  // but we can use fe portal to steal from the expect route if it exists
  // isset($boardData['posts']) && is_array($boardData['posts'])
  if (!$row['noPosts'] && (!empty($row['postCount']) || isset($row['fileCount']))) {
    /*
    $files = 0;
    //echo "[", print_r($boardData['posts'], 1), "]<br>\n";
    foreach($boardData['posts'] as $post) {
      if (isset($post['files'])) {
        $files += count($post['files']);
      }
    }
    */
    $threadstats_html = replace_tags($threadstats_tmpl, array(
      //'replies' => count($boardData['posts']) - 1,
      'replies' => $row['postCount'],
      'files'   => $row['fileCount'],
    ));
  }

  // but how do you inject HTML into the template...
  // I think it's maybe up to this function to create those structures...
  $p = array(
    'boardUri' => $boardUri,
    'beforeFormEndHtml' => '',
    'tags' => array(),
  );
  $pipelines[PIPELINE_BOARD_FOOTER_TMPL]->execute($p);

  $liveText_html = '';
  if (!$row['noPosts']) {
    $liveText_html = replace_tags($liveText_tmpl, array(
      'enabler' => $enabler_html,
      'enableAutoRefresh' => $row['threadClosed'] ? '' : 'checked',
      'url' => $_SERVER['REQUEST_URI'],
    ));
  }

  return replace_tags($tmpl, array_merge($p['tags'], array(
    'uri' => $boardUri,
    'url' => $_SERVER['REQUEST_URI'],
    //'boardNav' => $row['boardNav'],
    'threadstats' => $threadstats_html,
    'beforeFormEnd' => $p['beforeFormEndHtml'],
    // pipeline?
    // PA should be in PIPELINE_BOARD_FOOTER_TMPL now
    //'postactions' => $row['noPosts'] ? '' : renderPostActions($boardUri),
    'postForm' => $row['postForm'] ? str_replace('{{postForm}}', $row['postForm'], $postForm_tmpl) : '',
    'liveText' => $liveText_html,
  )));
}

// this isn't chainable
// it doesn't return a str
// see options in renderBoardPortalData, renderBoardPortalHeaderEngine, and renderBoardPortalFooterEngine
function getPostsPortal($boardUri, $boardData = false, $options = false) {
  //echo "[", print_r($boardData, 1), "]";
  //echo "options[", print_r($options, 1), "]";
  // auto-optimize if we can
  if (!isset($options['boardSettings'])) {
    // I think we need to deprecate this one...
    /*
    if (isset($boardData['json']['settings'])) {
      //echo "json fixing<br>\n";
      $options['boardSettings'] = $boardData['json']['settings'];
    } else
    */
    if (isset($boardData['settings'])) {
      //echo "fixing<br>\n";
      $options['boardSettings'] = $boardData['settings'];
    }
  }
  $row = renderPostsPortalData($boardUri, $boardData['pageCount'], $options);
  return array(
    'header' => renderPostsPortalHeaderEngine($row, $boardUri, $boardData),
    'footer' => renderPostsPortalFooterEngine($row, $boardUri, $boardData)
  );
}

// portals? header/footer split?
// is this function responsible for boardData? can't be if we're to be efficient
function renderPostsPortalHeader($boardUri, $boardData = false, $options = false) {
  if ($boardData === false) {
    // look up board data on-demand
    // FIXME: probably should bitch
    $boardData = getter_getBoard($boardUri);
  } else {
    if (!isset($options['boardSettings'])) {
      if (isset($boardData['settings'])) {
        //echo "fixing<br>\n";
        $options['boardSettings'] = $boardData['settings'];
      }
    }
  }
  $row = renderPostsPortalData($boardUri, $boardData['pageCount'], $options);
  return renderPostsPortalHeaderEngine($row, $boardUri, $boardData);
}

function renderPostsPortalFooter($boardUri, $boardData = false, $options = false) {
  if ($boardData === false) {
    // look up board data on-demand
    // FIXME: probably should bitch
    $boardData = getter_getBoard($boardUri);
  }
  $row = renderPostsPortalData($boardUri, $boardData['pageCount'], $options);
  echo renderPostsPortalFooterEngine($row, $boardUri, $boardData);
}

?>