<?php
$params = $get();

//echo "request[", print_r($request, 1), "]<br>\n";
$boardData = boardMiddleware($request);

// handle 404
if (!$boardData) return;
//echo "boardData[", gettype($boardData), print_r($boardData, 1), "]<br>\n";
//$data = getBoardByUri($boardData['uri']);
$data = $boardData;

// $data['json']['reports']
$reports = array();
if (isset($data['json']['reports'])) {
  foreach($data['json']['reports'] as $r) {
    $reports[] = array(
      'id' => $r['id'],
      'created_at' => $r['created_at'],
      'status' => $r['status'],
      'postid' => $r['postid'],
    );
  }
}
global $tpp;
// just pass through the settings for now...
if (!isset($boardData['json'])) $boardData['json'] = array();
boardRowFilter($boardData, $boardData['json'], array('jsonFields' => 'settings'));
// I don't think this is required
$posts_model = getPostsModel($boardData['uri']);
$boardData['threadCount'] = getBoardThreadCount($boardData['uri'], $posts_model);
$boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);

sendResponse2($reports, array('meta' => array('board' => $boardData)));
?>
