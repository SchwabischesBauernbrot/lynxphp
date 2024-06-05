<?php

global $portalsConfig;
$portalsConfig['board'] = array(
  // 'uri' => array('type' => 'params', 'name' => 'uri')
  'params' => array()
);

// a handler(router) adapter
function getPortalBoard($opts, $request) {
  global $portalData;
  //echo "<pre>getPortalBoard opts[", print_r($opts, 1), "]</pre>\n";
  //echo "<pre>getPortalBoard request[", print_r($request, 1), "]</pre>\n";

  // from backend portal
  //echo "<pre>getPortalBoard portalData[", print_r($portalData, 1), "]</pre>\n";

  //echo "<pre>getPortalBoard paramsCode[", print_r($opts['paramsCode'], 1), "]</pre>\n";
  //$config = getPortalBoardConfig();
  //global $portalsConfig;
  // meta coding
  $params = portal_getParamsFromContext($opts['paramsCode'], $request);
  //echo "<pre>getPortalBoard params[", print_r($params, 1), "]</pre>\n";

  //global $boardData;
  //echo "<pre>getPortalBoard boardData[", print_r($boardData, 1), "]</pre>\n";

  $boardUri = empty($request['params']['uri']) ? '' : $request['params']['uri'];
  $isPage1 = $_SERVER['REQUEST_URI'] === '/' . $boardUri . '/';

  // boardData['pageCount']
  $options = array(
    'board' => '',
    'pagenum' => empty($params['page']) ? ($isPage1 ? 1 : 0) : $params['page'],
    'noBoardHeaderTmpl' => empty($opts['noBoardHeaderTmpl']) ? false : true,
    'isThread' => empty($opts['isThread']) ? false : true,
    'threadClosed' => empty($opts['threadClosed']) ? false : true,
    // FIXME: this should default to on tbh
    'noPosts' => empty($opts['noPosts']) ? false : true,
  );
  //echo "<pre>board portal options", print_r($options, 1), "</pre>\n";
  // get page count
  // so on board_view this comes from a specific custom endpoint
  // maybe better solved by a portal linkage...
  // implying moving portals into common
  // so we can couple BE and FE coordination together
  // so header/footer can rely on data coming from BE EPs
  // should be similar to a BE handler
  // so if the page has no BE calls, we can just get what we need
  global $_portalData;
  //echo "<pre>_portalData", print_r($_portalData['board'], 1), "</pre>\n";
  $pageCount = 0;
  if (isset($_portalData['board']['pageCount'])) {
    $pageCount = $_portalData['board']['pageCount'];
    $uri = $params['uri'];
  } // else how? which EP
  else {
    //echo "<pre>opts", print_r($opts, 1), "</pre>\n";
    if (!empty($opts['uri'])) {
      $pageCount = $opts['pageCount'];
      $uri = $opts['uri'];
    } else {
      global $packages;
      // without portals=board we're missing the banner data
      // this hangs shit...
      // , 'portals' => 'boards'
      // should already auto-matically add it... right?
      $boardThreads = $packages['base_board_view']->useResource('board_page', array('uri' => $params['uri'], 'page' => $options['pagenum']));
      //echo "<pre>boardThreads", print_r($boardThreads, 1), "</pre>\n";

      // doesn't look like anything uses boardData past this point...
      global $boardData;
      // overboard doesn't have board or pageCount set
      if (!$boardData) {
        if (isset($boardThreads['board'])) {
          $boardData = $boardThreads['board'];
        } else {
          // overboard
          $boardData = array(
            'uri' => 'overboard',
            'description' => 'overboard',
          );
        }
      }
      //echo "<pre>boardThreads", print_r($boardThreads, 1), "</pre>\n";
      $pageCount = empty($boardThreads['pageCount']) ? 0 : $boardThreads['pageCount'];
      $uri = $params['uri'];
    }
  }
  // FIXME: pipelinable
  if ($uri !== 'overboard') {
    $boardSettings = getter_getBoardSettings($uri);
  } else {
    $boardSettings = array();
  }
  $row = renderBoardPortalData($uri, $pageCount, array(
    'pagenum' => $options['pagenum'],
    'noBoardHeaderTmpl' => $options['noBoardHeaderTmpl'],
    'isThread' => $options['isThread'],
    'threadClosed' => $options['threadClosed'],
    'boardSettings' => $boardSettings,
    'noPosts' => $options['noPosts'],
    'threadNum' => empty($params['num']) ? 0 : $params['num'],
    'siteSettings' => $portalData['board']['siteSettings']['site'],
    // not used
    //'userSettings' => $portalData['board']['siteSettings']['user'],
  ));
  return array(
    'uri' => $uri,
    'portalSettings' => $row,
    //'noPosts' => $options['noPosts'],
  );
}

// separate so overboard can inject multiple times
function generateJsBoardInfo($boardUri, $boardSettings, $options = false) {
  extract(ensureOptions(array(
    'first' => true,
  ), $options));
  $json = json_encode($boardSettings);
  // why not drop first option?
  $firstJs = $first ? 'const boardData = {}' : 'if (typeof(boardData) === \'undefined\') boardData = {}';
  // header/footer can be stripped here
  // could be passed as data-attributes too
  // not sure this should be embedded
  // since it's JS only maybe an ajax call?
  // can't use dot notation because we have boardUri starting with numbers
  return <<< EOB
<script>
$firstJs
boardData['$boardUri'] = $json
</script>
EOB;
}

// refactored out so we can share data between header/footer
// without having to recalculate it
function renderBoardPortalData($boardUri, $pageCount, $options = false) {
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
    'siteSettings'      => false,
  ), $options));

  $templates = loadTemplates('mixins/board_header');
  $tmpl = $templates['header'];
  $nav_wrapper_tmpl    = $templates['loop0'];
  $page_wrapper_tmpl    = $templates['loop1'];
  $pageLink_tmpl        = $templates['loop2'];
  $pageLinkCurrent_tmpl = $templates['loop3'];
  $boardNavLink_tmpl    = $templates['loop4'];
  $boardNavCurrent_tmpl = $templates['loop5'];
  $boardNavPrevLink_tmpl = $templates['loop6'];
  $boardNavPrevCurrentLink_tmpl = $templates['loop7'];
  $boardNavNextLink_tmpl = $templates['loop8'];
  $boardNavNextCurrentLink_tmpl = $templates['loop9'];

  // would be nice to have the board settings by here
  // so we can pass it in to control/hint the nav
  // we need boardSettings for pipelines (mainly nav)
  if ($boardSettings === false) {
    if (DEV_MODE) {
      echo "No boardSettings passed to renderBoardPortalData<Br>\n";
    }
    $boardSettings = getter_getBoardSettings($boardUri);
  }
  $nav_io = array(
    'boardUri' => $boardUri,
    // would be help to know what settings are used
    // settings_queueing_mode is used
    'boardSettings' => $boardSettings,
    'navItems' => array(
      array('label' => 'Index' , 'destinations' => array($boardUri . '/', $boardUri . '/page/1')),
      //array('label' => 'Catalog' , 'destinations' => $boardUri . '/catalog.html'),
      //'Index' => $boardUri . '/',
      //'Catalog' => $boardUri . '/catalog.html',
    ),
  );
  // these are really board actions...
  $pipelines[PIPELINE_BOARD_NAV]->execute($nav_io);

  $board_actions = action_getLevels();
  // we need to unify these defaults...
  // probably should live somewhere else and filter on where
  //$board_actions['all'][] = array('link' => '/' . $boardUri . '/', 'label' => 'View');
  // probably should live in base/board/settings
  $board_actions['bo'][] = array('link' => '/' . $boardUri . '/board_settings.php', 'label' => 'Settings');

  $action_io = array(
    'boardUri' => $boardUri,
    'b' => array(),
    'where' => $boardUri . '/',
    'actions'  => $board_actions,
  );
  // where does view come from
  $pipelines[PIPELINE_BOARD_ACTIONS]->execute($action_io);
  // remap output over the top of the input
  $board_actions = $action_io['actions'];
  // FIXME: expander?
  $board_actions_html1 = action_getHtml($board_actions, array(
    'boardUri' => $boardUri, 'where' => 'boards', 'join' => " | \n",
  ));
  // , 'nojs' => $nojs
  $board_actions_html2 = action_getExpandHtml($board_actions, array(
    'boardUri' => $boardUri, 'where' => $boardUri . '/'));

  $nav_html = getNav2($nav_io['navItems'], array(
    'list' => false,
    // handle no pages...
    //'selected' => $pageCount ? $selected : NULL,
    'selectedURL' => substr($_SERVER['REQUEST_URI'], 1),
    //'replaces' => array('uri' => $boardUri),
    // do it in the template
    'template' => $boardNavLink_tmpl, // url, label, classes, id
    'selected_template' => $boardNavCurrent_tmpl,
    //'prelabel' => '[',
    //'postlabel' => ']',
  ));
  $nav_html .= $board_actions_html1;

  $boardNav = '';
  if (!$isThread) {

    // do pages
    $pages_html = '';
    $pgTags = array('uri' => $boardUri);
    //echo "pagenum[$pagenum]<br>\n";
    if ($pagenum == 1 || !$pagenum) {
      // current prev
      $pages_html .= replace_tags($boardNavPrevCurrentLink_tmpl, $pgTags);
    } else {
      // link prev
      $pgTags['pagenum'] = $pagenum - 1;
      $pages_html .= replace_tags($boardNavPrevLink_tmpl, $pgTags);
    }
    for($p = 1; $p <= $pageCount; $p++) {
      if ($pagenum == $p) {
        // current
        $pgTags = array(
          'uri'     => $boardUri,
          'pagenum' => $p,
        );
        $pages_html .= replace_tags($pageLinkCurrent_tmpl, $pgTags);
      } else {
        $pgTags = array(
          'uri'     => $boardUri,
          'class'   => $pagenum == $p ? 'bold' : '',
          'pagenum' => $p,
        );
        $pages_html .= replace_tags($pageLink_tmpl, $pgTags);
      }
    }

    $pgTags = array('uri' => $boardUri);
    if ($pagenum == $pageCount || !$pagenum) {
      // current next
      $pages_html .= replace_tags($boardNavNextCurrentLink_tmpl, $pgTags);
    } else {
      // link prev
      $pgTags['pagenum'] = $pagenum + 1;
      $pages_html .= replace_tags($boardNavNextLink_tmpl, $pgTags);
    }

    // pop them into page_wrapper_tmpl
    $page_nav_html = replace_tags($page_wrapper_tmpl, array(
      'pages' => $pages_html,
      'boardNav' => $nav_html,
    ));
  } else {
    $page_nav_html = $nav_html;
  }
  if ($page_nav_html) {
    $boardNav = replace_tags($nav_wrapper_tmpl, array(
      'boardNav' => $page_nav_html,
    ));
  }
  //echo "<pre>boardNav[", htmlspecialchars(print_r($boardNav, 1)), "]</pre>\n";

  $p = array(
    'tags' => array(
      'board_header_top' => generateJsBoardInfo($boardUri, $boardSettings),
      'board_header_bottom' => '',
    ),
    'boardUri' => $boardUri,
  );
  //echo "noBoardHeaderTmpl[$noBoardHeaderTmpl]<Br>\n";
  if (!$noBoardHeaderTmpl) {
    // banner is injected here:
    //$pipelines[PIPELINE_BOARD_HEADER_TMPL]->execute($p);
  }

  // if threadNum, is it locked?
  //echo "threadNum[$threadNum]<br>\n";

  return array(
    'tmpl' => $tmpl,
    'tags' => $p['tags'],
    'isCatalog' => $isCatalog,
    'threadNum' => $threadNum,
    'pagenum' => $pagenum,
    'threadClosed' => $threadClosed,
    'threadSaged'  => $threadSaged,
    // used in footer
    'boardNav' => $boardNav,
    'noPosts'  => $noPosts,
    'maxMessageLength' => $maxMessageLength,
    'quick_reply' => $siteSettings['quick_reply'],
  );
}

function getPortalBoardHeader($data) {
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
  echo renderBoardPortalHeaderEngine($data['portalSettings'], $data['uri'], $boardData);
}

function renderBoardPortalHeaderEngine($row, $boardUri, $boardData) {
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

  //echo "<pre>row[", htmlspecialchars(print_r($row, 1)), "]</pre>\n";

  $form_html = '';
  // is thread open?
  if (!$row['noPosts']) {
    if ($row['quick_reply'] === '1') {
      $form_html = renderPostForm($boardUri, $renderPostFormUrl, $renderPostFormOptions);
    } else {
      //echo "no quick_reply<br>\n";
      $renderPostFormOptions['formId'] = 'top_postform';
      $renderPostFormOptions['showLink'] = false;
      $renderPostFormOptions['showClose'] = false;
      $form_html = renderPostForm($boardUri, $renderPostFormUrl, $renderPostFormOptions);
    }
  }


  return replace_tags($row['tmpl'], array_merge($row['tags'], array(
    'uri' => $boardUri,
    'url' => $_SERVER['REQUEST_URI'],
    'title' => $isCatalog ? '' : ' - ' . htmlspecialchars($boardData['title']),
    'description' => htmlspecialchars($boardData['description']),
    'postform' => $form_html,
    'sticknav' => $stickNav_html,
    'boardNav' => $row['boardNav'],
    'pretitle' => $isCatalog ? 'Catalog(' : '',
    'posttitle' => $isCatalog ? ')' : '',
    'linkStyle' => $isCatalog ? '' : ' style="color: var(--board-title)"',
    'vichanBanner' => $threadNum ? '' : ' style="display: none"',
    // Closed > Saged > Reply
    'mode' => $row['threadClosed'] ? 'Closed' : ($row['threadSaged'] ? 'Saged' : 'Reply'),
  )));
}

function getPortalBoardFooter($data) {
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
  echo renderBoardPortalFooterEngine($data['portalSettings'], $data['uri'], $boardData);
}

function renderBoardPortalFooterEngine($row, $boardUri, $boardData) {
  global $pipelines;
  //echo "<pre>", htmlspecialchars(print_r($row, 1)), "</pre>\n";

  $templates = loadTemplates('mixins/board_footer');
  $tmpl = $templates['header'];
  //$threadstats_tmpl = $templates['loop0'];
  //$postForm_tmpl = $templates['loop1'];
  //$liveText_tmpl = $templates['loop2'];

  $enabler_html = '';
  /*
  if (!$row['threadNum']) {
    // turn it off, why?
    $enabler_html = '<style>
#autoRefreshEnable {
  display: none;
}
</style>';
  }

  $threadstats_html = '';
  if (!$row['noPosts'] && isset($boardData['posts']) && is_array($boardData['posts'])) {
    $files = 0;
    //echo "[", print_r($boardData['posts'], 1), "]<br>\n";
    foreach($boardData['posts'] as $post) {
      if (isset($post['files'])) {
        $files += count($post['files']);
      }
    }
    $threadstats_html = replace_tags($threadstats_tmpl, array(
      'replies' => count($boardData['posts']) - 1,
      'files'   => $files,
    ));
  }
  */

  // but how do you inject HTML into the template...
  $p = array(
    'boardUri' => $boardUri,
    'beforeFormEndHtml' => '',
    'tags' => array()
  );
  $pipelines[PIPELINE_BOARD_FOOTER_TMPL]->execute($p);

  /*
  $liveText_html = '';
  if (!$row['noPosts']) {
    $liveText_html = replace_tags($liveText_tmpl, array(
      'enabler' => $enabler_html,
      'enableAutoRefresh' => $row['threadClosed'] ? '' : 'checked',
      'url' => $_SERVER['REQUEST_URI'],
    ));
  }
  */

  return replace_tags($tmpl, array_merge($p['tags'], array(
    'uri' => $boardUri,
    'url' => $_SERVER['REQUEST_URI'],
    'boardNav' => $row['boardNav'],
    'board_footer_top' => '',
    'board_footer_bottom' => '',
    //'threadstats' => $threadstats_html,
    // pipeline?
    //'postactions' => $row['noPosts'] ? '' : renderPostActions($boardUri),
    //'postForm' => $row['postForm'] ? str_replace('{{postForm}}', $row['postForm'], $postForm_tmpl) : '',
    //'liveText' => $liveText_html,
  )));
}

// this isn't chainable
// it doesn't return a str
// see options in renderBoardPortalData, renderBoardPortalHeaderEngine, and renderBoardPortalFooterEngine
function getBoardPortal($boardUri, $boardData = false, $options = false) {
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
  $row = renderBoardPortalData($boardUri, $boardData['pageCount'], $options);
  return array(
    'header' => renderBoardPortalHeaderEngine($row, $boardUri, $boardData),
    'footer' => renderBoardPortalFooterEngine($row, $boardUri, $boardData)
  );
}

// portals? header/footer split?
// is this function responsible for boardData? can't be if we're to be efficient
function renderBoardPortalHeader($boardUri, $boardData = false, $options = false) {
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
  $row = renderBoardPortalData($boardUri, $boardData['pageCount'], $options);
  return renderBoardPortalHeaderEngine($row, $boardUri, $boardData);
}

function renderBoardPortalFooter($boardUri, $boardData = false, $options = false) {
  if ($boardData === false) {
    // look up board data on-demand
    // FIXME: probably should bitch
    $boardData = getter_getBoard($boardUri);
  }
  $row = renderBoardPortalData($boardUri, $boardData['pageCount'], $options);
  echo renderBoardPortalFooterEngine($row, $boardUri, $boardData);
}

?>