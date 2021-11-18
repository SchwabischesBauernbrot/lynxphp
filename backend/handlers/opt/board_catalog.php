<?php

// https://a.4cdn.org/po/catalog.json
global $tpp;
$boardUri = $request['params']['board'];
$page = boardCatalog($boardUri);
if (!is_array($page)) {
  // boardCatalog handles this
  return;
}
// json fields?
$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
$pages = count($page);
// FIXME: just return a list of threads...
// also be able to page count?
$res = array();
for($i = 1; $i <= $pages; $i++) {
  $res[] = array(
    'page' => $i,
    'threads' => $page[$i],
  );
}
sendResponse2(array(
  'pages' => $res,
  'board' => $boardData,
));