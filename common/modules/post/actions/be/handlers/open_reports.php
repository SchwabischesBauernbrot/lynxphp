<?php
$params = $get();

$board = getQueryField('boardUri');

if (!$board) {
  return sendResponse(array(
    'writeMe' => true,
  ));
}

global $db;
$posts_model = getPostsModel($board);
$data = getBoardByUri($board);

$lynxReports = array();
if (isset($data['json']['reports']) && is_array($data['json']['reports'])) {
  foreach($data['json']['reports'] as $i=>$r) {
    if ($r['status'] === 'open') {
      $post = $db->findById($posts_model, $r['postid']);
      if (!$post) continue;
      $lynxReports[] = array(
        '_id' => $i, // could md5 it...
        'global' => false,
        'boardUri' => $board,
        // if postid is threadid, use postid instead
        'threadId' => $post['threadid'] ? $post['threadid'] : $post['postid'],
        'postId' => $r['postid'],
        'creation' => $r['created_at'], // FIXME js date?
      );
    }
  }
}

sendResponse(array(
  'reports' => $lynxReports,
));
?>