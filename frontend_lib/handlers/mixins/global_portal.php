<?php

function renderGlobalPortal() {
  return renderPortalHeader('global', array(
    'headerPipeline' => PIPELINE_GLOBALS_HEADER_TMPL,
    'navPipeline'    => PIPELINE_GLOBALS_NAV,
    'navItems' => array(),
  ));
}

?>
