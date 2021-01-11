<?php
$params = $get();

$board = boardOwnerMiddleware($request);

global $db;
$posts_model = getPostsModel($board);

// report-{{_id}}
$ids = array();
foreach($_POST as $k => $v) {
  if (substr($k, 0, 6) === 'report') {
    // FIXME; move the post existence check in here...
    $ids[] = substr($k, 7);
  }
}

$removedThreads = 0;
$removedPosts   = 0;

$data = getBoardByUri($board);
$nukes = array();
foreach($data['json']['reports'] as $id => $r) {
  if ($r['status'] === 'open' && in_array($id, $ids)) {
    $post = $db->findById($posts_model, $r['postid']);
    if (!$post) continue;
    if ($_POST['deleteContent']) {
      if (!$db->deleteById($posts_model, $r['postid'])) {
        // don't close this report because DB error
        continue;
      }
      if ($post['threadid']) {
        $removedPosts++;
      } else {
        $removedThreads++;
      }
    }
    unset($data['json']['reports'][$id]);
    $nukes[] = $id;
  }
}
if (count($nukes)) {
  updateBoard($board, $data);
}

sendResponse(array(
  'success' => 'ok',
  'ids' => $ids,
  'nukes' => $nukes,
  'removedThreads' => $removedThreads,
  'removedPosts' => $removedPosts,
));
?>