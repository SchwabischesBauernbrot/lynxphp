<?php

// Thread list
// https://a.4cdn.org/po/threads.json

global $tpp;
$boardUri = $request['params']['board'];
$page = boardCatalog($boardUri);
if (!is_array($page)) {
  return sendJson(array('meta' => array('err' => 'Board not found')), array('code' => 404));
}
$pages = count($page);
$res = array();
for($i = 1; $i <= $pages; $i++) {
  $threads = array();
  foreach($page[$i] as $t) {
    // no, last_modified, replies
    $thread = array(
      'no'            => $t['no'],
      //'replies'       => empty($t['reply_count']) ? null : $t['reply_count'],
      'replies'       => $t['reply_count'],
      'last_modified' => $t['updated_at']
    );
    $threads[] = $thread;
  }
  $res[] = array(
    'page' => $i,
    'threads' => $threads,
  );
}
sendJson($res);
