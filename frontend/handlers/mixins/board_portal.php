<?php

// portals? header/footer split?
// is this function responsible for boardData? can't be if we're to be efficient
function renderBoardPortalHeader($boardUri, $boardData = false, $options = false) {
  global $pipelines;
  if ($boardData === false) {
    // FIXME: look up board data on-demand
  }
  $pagenum   = 0;
  $isCatalog = 0;
  $isThread  = 0;
  $threadNum = 0;
  if (is_array($options)) {
    if (isset($options['pagenum']))   $pagenum   = $options['pagenum'];
    if (isset($options['isCatalog'])) $isCatalog = $options['isCatalog'];
    if (isset($options['isThread']))  $isThread  = $options['isThread'];
    if (isset($options['threadNum'])) $threadNum = $options['threadNum'];
  }

  $templates = loadTemplates('mixins/board_header');
  $tmpl = $templates['header'];
  $page_wrapper_tmpl = $templates['loop0'];
  $pageLink_tmpl     = $templates['loop1'];
  $boardNaLink_tmpl  = $templates['loop2'];

  $navItems = array(
    '[Index]' => '{{uri}}/',
    '[Catalog]' => '{{uri}}/catalog',
  );
  $pipelines[PIPELINE_BOARD_NAV]->execute($navItems);

  $nav_html = getNav2($navItems, array(
    'list' => false,
    // handle no pages...
    //'selected' => $pageCount ? $selected : NULL,
    'selectedURL' => substr($_SERVER['REQUEST_URI'], 1),
    'replaces' => array('uri' => $boardUri),
    // do it in the template
    //'prelabel' => '[',
    //'postlabel' => ']',
  ));

  $boardNav = '';
  if (!$isThread) {

    $pages_html = '';
    // FIXME: wire this up
    for($p = 1; $p <= $boardData['pageCount']; $p++) {
      $tmp = $pageLink_tmpl;
      // FIXME: use replace_tags
      $tmp = str_replace('{{uri}}', $boardUri, $tmp);
      // bold
      $tmp = str_replace('{{class}}', $pagenum == $p ? 'bold' : '', $tmp);
      $tmp = str_replace('{{pagenum}}', $p, $tmp);
      $pages_html .= $tmp;
    }

    $boardNav = replace_tags($page_wrapper_tmpl, array(
      'pages' => $pages_html,
      'boardNav' => $nav_html,
    ));
  } else {
    $boardNav = $nav_html;
  }

  $stickNav_html = '';
  $pipelines[PIPELINE_BOARD_STICKY_NAV]->execute($stickNav_html);

  $p = array(
    'tags' => array(),
    'boardUri' => $boardUri,
  );
  // banner is injected here:
  $pipelines[PIPELINE_BOARD_HEADER_TMPL]->execute($p);

  $renderPostFormOptions = array();
  $renderPostFormUrl = $boardUri . '/';
  if ($threadNum) {
    $renderPostFormOptions['reply'] = $threadNum;
    $renderPostFormUrl .= 'thread/' . $threadNum . '.html';
  } else
  if ($pagenum) {
    $renderPostFormOptions['page'] = $pagenum;
    $renderPostFormUrl .= 'page/' . $pagenum;
  }

  return replace_tags($tmpl, array_merge($p['tags'], array(
    'uri' => $boardUri,
    'url' => $_SERVER['REQUEST_URI'],
    'title' => $isCatalog ? '' : ' - ' . htmlspecialchars($boardData['title']),
    'description' => htmlspecialchars($boardData['description']),
    'postform' => renderPostForm($boardUri, $renderPostFormUrl, $renderPostFormOptions),
    'sticknav' => $stickNav_html,
    'boardNav' => $boardNav,
    'pretitle' => $isCatalog ? 'Catalog(' : '',
    'posttitle' => $isCatalog ? ')' : '',
    'linkStyle' => $isCatalog ? '' : ' style="color: var(--board-title)"',
  )));
}

function renderBoardPortalFooter($boardUri, $pageTotal, $pagenum = 0) {
  global $pipelines;

  $templates = loadTemplates('mixins/board_footer');
  $tmpl = $templates['header'];

  $p = array(
    'tags' => array()
  );
  $pipelines[PIPELINE_BOARD_FOOTER_TMPL]->execute($p);

  return replace_tags($tmpl, array_merge($p['tags'], array(
    'uri' => $boardUri,
    'url' => $_SERVER['REQUEST_URI'],
    'title' => htmlspecialchars($boardData['title']),
    'description' => htmlspecialchars($boardData['description']),
    'boardNav' => $nav_html,
  )));
}

?>