<?php

function renderGlobalPortal() {
  global $pipelines;

  $templates = loadTemplates('mixins/global_header');

  $p = array(
    'tags' => array()
  );
  $pipelines[PIPELINE_GLOBALS_HEADER_TMPL]->execute($p);

  // default globals items
  $navItems = array(
  );
  $pipelines[PIPELINE_GLOBALS_NAV]->execute($navItems);
  $nav_html = getNav2($navItems, array(
    'selectedURL' => substr($_SERVER['REQUEST_URI'], 1),
    'prelabel' => '[',
    'postlabel' => ']',
  ));

  return replace_tags($templates['header'], array_merge($p['tags'], array(
    'nav' => $nav_html,
  )));
}

?>
