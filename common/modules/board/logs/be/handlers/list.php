<?php
$params = $get();

$boardData = boardMiddleware($request);
//print_r($boardData);
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
boardRowFilter($boardData, $boardData['json'], array('jsonFields' => 'settings'));
// I don't think this is required
$posts_model = getPostsModel($boardUri);
$boardData['threadCount'] = getBoardThreadCount($boardData['uri'], $posts_model);
$boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);

sendResponse($reports, 200, '', array('board' => $boardData));
?>
