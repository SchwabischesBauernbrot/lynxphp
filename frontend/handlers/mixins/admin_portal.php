<?php

function renderAdminPortal() {
  global $pipelines;

  $templates = loadTemplates('mixins/admin_header');

  $p = array(
    'tags' => array()
  );
  $pipelines[PIPELINE_ADMIN_HEADER_TMPL]->execute($p);

  // default admin items
  $navItems = array(
    'Settings' => 'admin/settings',
    'Modules' => 'admin/modules',
    // should be iframe'd or more integrated...
    'System' => 'admin/install',
  );
  $pipelines[PIPELINE_ADMIN_NAV]->execute($navItems);
  $nav_html = getNav2($navItems, array(
    'selectedURL' => substr($_SERVER['REQUEST_URI'], 1),
    'prelabel' => '[',
    'postlabel' => ']',
  ));

  return replace_tags($templates['header'], array_merge($p['tags'], array(
    'adminNav' => $nav_html,
  )));
}

?>
