<?php

// FIXME: we need access to package
$params = $getHandler();

// do we own this board?
//$boardUri = $request['params']['uri'];
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

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

?>