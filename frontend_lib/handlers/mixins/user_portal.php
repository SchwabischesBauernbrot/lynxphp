<?php

function renderUserPortalHeader() {
  return renderPortalHeader('user', array(
    'headerPipeline' => PIPELINE_USER_HEADER_TMPL,
    'navPipeline'    => PIPELINE_USER_NAV,
    'navItems' => array(
      'general' => 'user/settings/general',
      'theme' => 'user/settings/theme',
    ),
  ));
}

?>
