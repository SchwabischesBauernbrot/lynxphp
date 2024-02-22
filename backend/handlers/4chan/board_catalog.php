<?php

// https://a.4cdn.org/po/catalog.json

global $tpp;
$boardUri = $request['params']['board'];
$page = boardCatalog($boardUri);
if (!is_array($page)) {
  return sendJson(array('meta' => array('err' => 'Board not found')), array('code' => 404));
}
$pages = count($page);
$res = array();
for($i = 1; $i <= $pages; $i++) {
  $res[] = array(
    'page' => $i,
    'threads' => $page[$i],
  );
}
sendJson($res);