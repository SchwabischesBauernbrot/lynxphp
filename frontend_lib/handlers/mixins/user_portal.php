<?php

function renderUserPortalHeader() {
  $test = substr($_SERVER['REQUEST_URI'], 1);
  $portalOptions = array(
    'headerPipeline' => PIPELINE_USER_HEADER_TMPL,
    'navPipeline'    => PIPELINE_USER_NAV,
    'navItems' => array(
      'general' => 'user/settings/general',
      'theme' => 'user/settings/theme',
    ),
  );
  if ($test === 'user/settings') {
    $portalOptions['useNavFirstItem'] = true;
  }
  return renderPortalHeader('user', $portalOptions);
}

?>
