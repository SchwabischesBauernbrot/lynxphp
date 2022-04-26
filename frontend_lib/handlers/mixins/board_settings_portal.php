<?php

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
  $tmpl = $templates['header'];

  //$page_wrapper_tmpl = $templates['loop0'];
  //$pageLink_tmpl     = $templates['loop1'];
  //$boardNavLink_tmpl  = $templates['loop2'];

  // would be nice to have the board settings by here
  // so we can pass it in to control/hint the nav
  // we need boardSettings for pipelines (mainly nav)
  if ($boardSettings === false) {
    if (DEV_MODE) {
      echo "No boardSettings passed to renderBoardSettingsPortalData<Br>\n";
    }
    $boardData = getBoard($boardUri);
    if (isset($boardData['settings'])) {
      $boardSettings = $boardData['settings'];
    }
    //print_r($boardSettings);
  }
  $nav_io = array(
    'boardUri' => $boardUri,
    // would be help to know what settings are used
    // settings_queueing_mode is used
    'boardSettings' => $boardSettings,
    'navItems' => array(
    ),
  );
  $pipelines[PIPELINE_BOARD_SETTING_NAV]->execute($nav_io);

  $nav_html = getNav($nav_io['navItems'], array(
    'list' => false,
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
    $pipelines[PIPELINE_BOARD_HEADER_TMPL]->execute($p);
  }

  // if threadNum, is it locked?
  $form_html = $threadClosed ? '' : renderPostFormHTML($boardUri, array(
    'showClose' => false, 'formId' => 'bottom_postform',
    'reply' => $threadNum, 'maxMessageLength' => $maxMessageLength,
  ));

  return array(
    'tmpl' => $tmpl,
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

  return replace_tags($row['tmpl'], array_merge($row['tags'], array(
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

function renderBoardSettingsPortalFooterEngine($row, $boardUri, $boardData) {
  global $pipelines;
  return '';

  $templates = loadTemplates('board_settings');
  $tmpl = $templates['header'];
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
  echo renderBoardSettingsPortalFooterEngine($row, $boardUri, $boardData);
}

?>