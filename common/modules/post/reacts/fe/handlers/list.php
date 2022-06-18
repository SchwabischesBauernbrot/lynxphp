<?php

$params = $getHandler();

$boardUri = $request['params']['uri'];

// get a list of reacts from backend
$reacts = $pkg->useResource('list', array('boardUri' => $boardUri));

/*
$templates = moduleLoadTemplates('banner_listing', __DIR__);

$tmpl = $templates['header'];
$banner_tmpl = $templates['loop0'];
*/
global $boardData;
if (empty($boardData)) {
  $boardData = getBoard($boardUri);
}

$tmpl = '';
$tmpl .= '<a href="' . $boardUri . '/settings/reacts/add.html">Add custom react</a>' . "<br>\n";
//$tmpl .= print_r($reacts, 1);

$tmpl .= '<table width=100%>';
$tmpl .= '<tr><th>name<th>text<th>Lock<th>Hide<th>Created<th>actions' . "\n";
foreach($reacts as $r) {
  // reactid, board_uri, text, image, w, h, lock_default, hide_default, created_at
  $tmpl .= '<tr><th><nobr>' . $r['name'] . '</nobr><td><nobr>' . $r['text'] . "</nobr>\n";
  $tmpl .= '<td>' . $r['lock_default'];
  $tmpl .= '<td>' . $r['hide_default'];
  $tmpl .= '<td>' . date('Y-m-d H:i:s', $r['created_at']);
  $tmpl .= '<td><a href="' . $boardUri . '/settings/reacts/' . $r['reactid']. '/delete.html">X</a>';
}
$tmpl .= '</table>';

$boardHeader = renderBoardSettingsPortalHeader($boardUri, $boardData);
$boardFooter = '';
//$boardFooter = renderBoardSettingsPortalFooter($boardUri, $boardData);

wrapContent($boardHeader . $tmpl . $boardFooter);

?>