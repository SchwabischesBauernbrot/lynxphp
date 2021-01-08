<?php

function renderBoardNav($boardUri, $pageCount, $selected) {
  global $pipelines;
  $templates = loadTemplates('mixins/board_nav');

  $tmpl = $templates['header'];
  $page_tmpl = $templates['loop0'];

  $pages_html = '';
  for($p = 1; $p <= $pageCount; $p++) {
    $tmp = $page_tmpl;
    $tmp = str_replace('{{uri}}', $boardUri, $tmp);
    // bold
    $tmp = str_replace('{{class}}', $selected == $p ? 'bold' : '', $tmp);
    $tmp = str_replace('{{pagenum}}', $p, $tmp);
    $pages_html .= $tmp;
  }
  $tmpl = str_replace('{{pages}}', $pages_html, $tmpl);

  $navItems = array(
    '[Index]' => '{{uri}}/',
    '[Catalog]' => '{{uri}}/catalog',
  );
  $pipelines['board_nav']->execute($navItems);

  $nav_html = getNav2($navItems, array(
    'list' => false,
    // handle no pages...
    //'selected' => $pageCount ? $selected : NULL,
    'selectedURL' => substr($_SERVER['REQUEST_URI'], 1),
    'replaces' => array('uri' => $boardUri),
  ));

  $tmpl = str_replace('{{boardNav}}', $nav_html, $tmpl);
  return $tmpl;
}

?>
