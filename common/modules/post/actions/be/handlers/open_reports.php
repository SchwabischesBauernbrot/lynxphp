<?php
$params = $get();

$board = getQueryField('boardUri');

//echo "board[$board]<br>\n";

global $db;

if (!$board) {
  // get a list of all boards...
  $boards = listBoards();
  $lynxReports = array();
  foreach($boards as $b) {
    //echo "<pre>", print_r($b, 1), "</pre>";
    $posts_model = getPostsModel($b['uri']);
    $data = getBoardByUri($b['uri']);
    //echo "<pre>", print_r($data, 1), "</pre>";
    if (!empty($data['json']['reports'])) {
      foreach($data['json']['reports'] as $i=>$r) {
        if ($r['status'] === 'open') {
          $post = $db->findById($posts_model, $r['postid']);
          if (!$post) continue;
          $lynxReports[] = array(
            '_id' => $r['id'], // could md5 it...
            'global' => empty($r['global']) ? null : $r['global'],
            'boardUri' => $b['uri'],
            // if postid is threadid, use postid instead
            'threadId' => $post['threadid'] ? $post['threadid'] : $post['postid'],
            'postId' => $r['postid'],
            'creation' => $r['created_at'], // FIXME js date?
          );
        }
      }
    }
  }
  return sendResponse(array(
    'reports' => $lynxReports,
  ));
}

$posts_model = getPostsModel($board);
//$data = getBoardByUri($board);
$row = getBoardRaw($board);
//echo "<pre>", print_r($row, 1), "</pre>";
$json = json_decode($row['json'], true);
$data = array('json' => $json);

//echo "<pre>", print_r($data, 1), "</pre>";

$lynxReports = array();
if (isset($data['json']['reports']) && is_array($data['json']['reports'])) {
  foreach($data['json']['reports'] as $i=>$r) {
    //echo "<pre>", print_r($r, 1), "</pre>";
    if ($r['status'] === 'open') {
      $post = $db->findById($posts_model, $r['postid']);
      if (!$post) continue;
      $lynxReports[] = array(
        '_id' => $r['id'], // could md5 it...
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

// weird CF was agrgressively caching this...
sendResponse(array(
  'reports' => $lynxReports,
));
?>