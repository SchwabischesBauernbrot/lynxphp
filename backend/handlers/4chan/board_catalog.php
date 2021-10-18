<?php

// https://a.4cdn.org/po/catalog.json

global $tpp;
$boardUri = $request['params']['board'];
$page = boardCatalog($boardUri);
if (!is_array($page)) {
  return sendRawResponse(array(), 404, 'Board not found');
}
$pages = count($page);
$res = array();
for($i = 1; $i <= $pages; $i++) {
  $res[] = array(
    'page' => $i,
    'threads' => $page[$i],
  );
}
sendRawResponse($res);