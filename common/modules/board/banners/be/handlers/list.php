<?php
$params = $get();

$boardData = boardMiddleware($request);
if (!$boardData) {
  return sendResponse(array());
}

global $db, $models, $tpp;
$res = $db->find($models['board_banner'], array('criteria' => array(
  array('board_id', '=', $boardData['boardid']),
)));
$banners = $db->toArray($res);
// FIXME: boardData doesn't have json now

// just pass through the settings for now...
$json = empty($boardData['json']) ? '{}' : $boardData['json'];
boardRowFilter($boardData, $json, array('jsonFields' => 'settings'));
// I don't think this is required
$posts_model = getPostsModel($boardData['uri']);
$boardData['threadCount'] = getBoardThreadCount($boardData['uri'], $posts_model);
$boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);

sendResponse2($banners, array(
  'meta' => array('board' => $boardData)
));
?>
