<?php

// https://a.4cdn.org/po/catalog.json

global $tpp;
$boardUri = $request['params']['board'];
$page = boardCatalog($boardUri);
if (!is_array($page)) {
  // boardCatalog handles this
  return;
}
$pages = count($page);
$res = array();
for($i = 1; $i <= $pages; $i++) {
  $res[] = array(
    'page' => $i,
    'threads' => $page[$i],
  );
}
if (getQueryField('prettyPrint')) {
  echo '<pre>', json_encode($res, JSON_PRETTY_PRINT), "</pre>\n";
} else {
  echo json_encode($res);
}
