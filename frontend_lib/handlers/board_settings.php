<?php

function getBoardSettingsHandler($request) {
  $boardUri = $request['params']['uri'];
  getBoardSettings($boardUri);
}

function getBoardSettings($boardUri) {
  global $pipelines;
  $templates = loadTemplates('board_settings');
  $tmpl = $templates['header'];

  $io = array(
    'navItems' => array(),
    'boardUri' => $boardUri,
  );
  $pipelines[PIPELINE_BOARD_SETTING_NAV]->execute($io);
  $nav_html = getNav($io['navItems'], array(
    'replaces' => array('uri' => $boardUri),
  ));

  $tmpl = str_replace('{{nav}}', $nav_html, $tmpl);
  //$pipelines['boardSettingTmpl']->execute($tmpl);
  wrapContent($tmpl);
}

?>