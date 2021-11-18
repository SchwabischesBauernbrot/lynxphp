<?php

// FIXME: split into header/footer functions
function renderAdminPortal() {
  return renderPortalHeader('admin', array(
    'headerPipeline' => PIPELINE_ADMIN_HEADER_TMPL,
    'navPipeline'    => PIPELINE_ADMIN_NAV,
    'navItems' => array(
      'Settings' => 'admin/settings.html',
      'FE Routes' => 'admin/fe_routes.php',
      'BE Routes' => 'admin/be_routes.php',
      'Modules' => 'admin/modules.php',
      // should be iframe'd or more integrated...
      'System' => 'admin/install.php',
    ),
  ));
}

?>
