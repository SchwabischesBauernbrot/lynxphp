<?php

global $portalsConfig;
$portalsConfig['admin'] = array(
  'params' => array()
);

function getPortalAdmin($opts) {

}

// FIXME: split into header/footer functions
function renderAdminPortal() {
  $navItems = array(
    'Settings' => 'admin/settings.html',
    'FE Routes' => 'admin/fe_routes.php',
    'BE Routes' => 'admin/be_routes.php',
    'Modules' => 'admin/modules.php',
    // should be iframe'd or more integrated...
    'System' => 'admin/install.php',
  );

  $adminSettings = getCompiledSettings('admin');
  $settingSubItems = array(
    //array('label' => 'site', 'destinations' => 'admin/settings/site.html')
  );
  foreach($adminSettings as $nav => $ni) {
    $settingSubItems[] = array(
      'label' => $nav,
      'destinations' => 'admin/settings/' . $nav . '.html',
    );
  }

  $navItems2 = array(
    array('label' => 'Settings', 'subItems' => $settingSubItems),
    array('label' => 'FE Routes', 'destinations' => 'admin/fe_routes.php'),
    array('label' => 'BE Routes', 'destinations' => 'admin/be_routes.php'),
    array('label' => 'Modules', 'destinations' => 'admin/modules.php'),
    // should be iframe'd or more integrated...
    array('label' => 'System', 'destinations' => 'admin/install.php'),
  );
  $adminSettings = getCompiledSettings('admin');

  // this function isn't in lib.portal, where is it?
  return renderPortalHeader('admin', array(
    'headerPipeline' => PIPELINE_ADMIN_HEADER_TMPL,
    'navPipeline'    => PIPELINE_ADMIN_NAV,
    //'navItems'       => $navItems,
    'navItems2'       => $navItems2,
  ));
}

function getPortalAdminHeader($row) {
  echo renderAdminPortal();
}

function getPortalAdminFooter($row) {
  echo renderPortalFooter('admin');
}


?>
