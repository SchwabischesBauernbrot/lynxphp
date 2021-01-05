<?php

function renderAdminPortal() {
  global $pipelines;

  $templates = loadTemplates('mixins/admin_header');
  $tmpl = $templates['header'];

  $p = array(
    'tags' => array()
  );
  $pipelines[PIPELINE_ADMIN_HEADER_TMPL]->execute($p);
  foreach($p['tags'] as $s => $r) {
    $tmpl = str_replace('{{' . $s . '}}', $r, $tmpl);
  }

  // default admin items
  $navItems = array(
    'Modules' => 'admin/modules',
  );
  $pipelines[PIPELINE_ADMIN_NAV]->execute($navItems);
  $nav_html = getNav2($navItems, array(
    'selectedURL' => substr($_SERVER['REQUEST_URI'], 1),
    'prelabel' => '[',
    'postlabel' => ']',
  ));

  $tmpl = str_replace('{{adminNav}}', $nav_html, $tmpl);
  return $tmpl;
}

?>
