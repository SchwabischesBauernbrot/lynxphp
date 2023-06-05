<?php

// code and data for portal

// should the metadata be separate from the code
// so we dont need the code if we want to peak at the metadata
// well in the current system, there is no need for peaking
// we either use a portal or don't
// and no harm in iterating over all the portals and loading them

// get data for this
// a function call is a bit safer than returning from the include
// should this be a global, so it's not a call but a global
/*
function getPortalBoardSettingsConfig() {
  // could put params code here
  // do we need SID?
  return
}
*/

global $portalsConfig;
$portalsConfig['boardSettings'] = array(
  // wouldn't this depend on the route itself... *sigh*
  'params' => array('uri' => array('type' => 'params', 'name' => 'uri'))
);

// a handler(router) adapter
function getPortalBoardSettings($opts, $request) {
  //echo "getPortalBoardSettings<br>\n";
  //echo "<pre>getPortalBoardSettings row[", print_r($row, 1), "]</pre>\n";
  //echo "<pre>getPortalBoardSettings request[", print_r($request, 1), "]</pre>\n";

  //$config = getPortalBoardSettingsConfig();
  global $portalsConfig;
  // meta coding
  $params = portal_getParamsFromContext($portalsConfig['boardSettings']['params'], $request);
  //echo "<pre>getPortalBoardSettings params[", print_r($params, 1), "]</pre>\n";
  $boardSettings = getter_getBoardSettings($params['uri']);
  $row = renderBoardSettingsPortalData($params['uri'], array(
    'boardSettings' => $boardSettings,
  ));
  return array(
    'uri' => $params['uri'],
    'portalSettings' => $row,
  );
}

// refactored out so we can share data between header/footer
// without having to recalculate it
function renderBoardSettingsPortalData($boardUri, $options = false) {
  global $pipelines;

  extract(ensureOptions(array(
    'pagenum'   => 0,
    'isCatalog' => false,
    'isThread'  => false,
    'threadNum' => 0,
    'noBoardHeaderTmpl' => false,
    // turns off post_form:
    'threadClosed'      => false,
    'maxMessageLength'  => false,
    'boardSettings'     => false,
  ), $options));

  // needs to be moved to mixins now
  $templates = loadTemplates('board_settings');

  //$page_wrapper_tmpl = $templates['loop0'];
  //$pageLink_tmpl     = $templates['loop1'];
  //$boardNavLink_tmpl  = $templates['loop2'];

  // would be nice to have the board settings by here
  // so we can pass it in to control/hint the nav
  // we need boardSettings for pipelines (mainly nav)
  //echo "boardSettings[$boardSettings]<br>\n";
  if ($boardSettings === false) {
    if (DEV_MODE) {
      echo "No boardSettings passed to renderBoardSettingsPortalData<Br>\n";
    }
    $boardSettings = getter_getBoardSettings($boardUri);
    //print_r($boardSettings);
  }
  // print_r($boardSettings);
  $nav_io = array(
    'boardUri' => $boardUri,
    // would be help to know what settings are used
    // settings_queueing_mode is used
    'boardSettings' => $boardSettings,
    'navItems' => array(
    ),
  );

  $blocks = getCompiledSettings('bo');
  //echo "<pre>", print_r($blocks, 1), "</pre>\n";
  foreach($blocks as $section => $fs) {
    if ($section === 'board') {
      // special case for now
      $io['navItems']['Board settings'] = $boardUri . '/settings/board.html';
    } else {
      $lbl = getCompiledSettingsSectionLabel('bo', $section);
      // if (!$lbl) $lbl=$section
      $nav_io['navItems'][$lbl] = $boardUri . '/settings/' . $section;
    }
  }

  // FIXME: convert to getNav2
  $pipelines[PIPELINE_BOARD_SETTING_NAV]->execute($nav_io);

  $nav_html = getNav($nav_io['navItems'], array(
    'list' => true,
    'replaces' => array('uri' => $boardUri),
    // handle no pages...
    //'selected' => $pageCount ? $selected : NULL,
    'selectedURL' => substr($_SERVER['REQUEST_URI'], 1),
    //'replaces' => array('uri' => $boardUri),
    // do it in the template
    //'template' => $boardNavLink_tmpl, // url / label
    //'prelabel' => '[',
    //'postlabel' => ']',
  ));

  /*
  $boardNav = '';
  if (!$isThread) {
    // do pages
    $pages_html = '';
    // FIXME: wire this up
    for($p = 1; $p <= $pageCount; $p++) {
      $pgTags = array(
        'uri'     => $boardUri,
        'class'   => $pagenum == $p ? 'bold' : '',
        'pagenum' => $p,
      );
      $pages_html .= replace_tags($pageLink_tmpl, $pgTags);
    }

    // pop them into page_wrapper_tmpl
    $boardNav = replace_tags($page_wrapper_tmpl, array(
      'pages' => $pages_html,
      'boardNav' => $nav_html,
    ));
  } else {
  }
  */
  $boardNav = $nav_html;

  $p = array(
    'tags' => array(
      'board_header_top' => '',
      'board_header_bottom' => '',
    ),
    'boardUri' => $boardUri,
  );
  //echo "noBoardHeaderTmpl[$noBoardHeaderTmpl]<Br>\n";
  if (!$noBoardHeaderTmpl) {
    // banner is injected here:
    $pipelines[PIPELINE_BOARD_SETTING_HEADER_TMPL]->execute($p);
  }

  // if threadNum, is it locked?
  $form_html = $threadClosed ? '' : renderPostFormHTML($boardUri, array(
    'showClose' => false, 'formId' => 'bottom_postform',
    'reply' => $threadNum, 'maxMessageLength' => $maxMessageLength,
  ));

  //echo "returning<br>\n";

  return array(
    'tmpl' => array(
      'header' => $templates['header'],
      'footer' => $templates['loop0'],
    ),
    'tags' => $p['tags'],
    'isCatalog' => $isCatalog,
    'threadNum' => $threadNum,
    'pagenum' => $pagenum,
    // used in footer
    'boardNav' => $boardNav,
    'postForm' => $form_html,
    'maxMessageLength' => $maxMessageLength,
  );
}

function getPortalBoardSettingsHeader($data) {
  global $boardData; // better than nothing
  //echo "getPortalBoardSettingsHeader<br>\n";
  echo renderBoardSettingsPortalHeaderEngine($data['portalSettings'], $data['uri'], $boardData);
}

// why is the engine separate?
function renderBoardSettingsPortalHeaderEngine($row, $boardUri, $boardData) {
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

  return replace_tags($row['tmpl']['header'], array_merge($row['tags'], array(
    'uri' => $boardUri,
    'url' => $_SERVER['REQUEST_URI'],
    'title' => $isCatalog ? '' : ' - ' . htmlspecialchars($boardData['title']),
    'description' => htmlspecialchars($boardData['description']),
    // if postForm is set, the thread is not closed
    'postform' => $row['postForm'] ? renderPostForm($boardUri, $renderPostFormUrl, $renderPostFormOptions) : '',
    'sticknav' => $stickNav_html,
    'nav' => $row['boardNav'],
    'pretitle' => $isCatalog ? 'Catalog(' : '',
    'posttitle' => $isCatalog ? ')' : '',
    'linkStyle' => $isCatalog ? '' : ' style="color: var(--board-title)"',
  )));
}

function getPortalBoardSettingsFooter($data) {
  global $boardData; // better than nothing
  echo renderBoardSettingsPortalFooterEngine($data['portalSettings'], $data['uri'], $boardData);
}

function renderBoardSettingsPortalFooterEngine($row, $boardUri, $boardData) {
  global $pipelines;
  $tmpl = $row['tmpl']['footer'];
  return $tmpl;

  //$templates = loadTemplates('board_settings');
  $tmpl = $row['tmpl'];
  $threadstats_tmpl = $templates['loop0'];
  $postForm_tmpl = $templates['loop1'];

  $threadstats_html = '';
  if (isset($boardData['posts']) && is_array($boardData['posts'])) {
    $files = 0;
    //echo "[", print_r($boardData['posts'], 1), "]<br>\n";
    foreach($boardData['posts'] as $post) {
      $files += count($post['files']);
    }
    $threadstats_html = replace_tags($threadstats_tmpl, array(
      'replies' => count($boardData['posts']) - 1,
      'files'   => $files,
    ));
  }

  $p = array(
    'tags' => array()
  );
  $pipelines[PIPELINE_BOARD_FOOTER_TMPL]->execute($p);

  return replace_tags($tmpl, array_merge($p['tags'], array(
    'uri' => $boardUri,
    'url' => $_SERVER['REQUEST_URI'],
    'boardNav' => $row['boardNav'],
    'threadstats' => $threadstats_html,
    'postactions' => renderPostActions($boardUri),
    'postForm' => $row['postForm'] ? str_replace('{{postForm}}', $row['postForm'], $postForm_tmpl) : '',
  )));
}

// this isn't chainable
// it doesn't return a str
function getBoardSettingsPortal($boardUri, $boardData = false, $options = false) {
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
  $row = renderBoardSettingsPortalData($boardUri, $options);
  return array(
    'header' => renderBoardSettingsPortalHeaderEngine($row, $boardUri, $boardData),
    'footer' => renderBoardSettingsPortalFooterEngine($row, $boardUri, $boardData)
  );
}

// portals? header/footer split?
// is this function responsible for boardData? can't be if we're to be efficient
// why is the engine separate?
function renderBoardSettingsPortalHeader($boardUri, $boardData = false, $options = false) {
  if ($boardData === false) {
    // FIXME: look up board data on-demand
  }
  $row = renderBoardSettingsPortalData($boardUri, $options);
  return renderBoardSettingsPortalHeaderEngine($row, $boardUri, $boardData);
}

function renderBoardSettingsPortalFooter($boardUri, $boardData = false, $options = false) {
  if ($boardData === false) {
    // FIXME: look up board data on-demand
  }
  $row = renderBoardSettingsPortalData($boardUri, $options);
  return renderBoardSettingsPortalFooterEngine($row, $boardUri, $boardData);
}

?>