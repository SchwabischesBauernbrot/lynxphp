<?php
$params = $get();

$boardData = boardMiddleware($request);

$data = getBoardByUri($boardData['uri']);

// $data['json']['reports']
$reports = array();
foreach($data['json']['reports'] as $r) {
  $reports[] = array(
    'id' => $r['id'],
    'created_at' => $r['created_at'],
    'status' => $r['status'],
    'postid' => $r['postid'],
  );
}

sendResponse($reports);

?>
