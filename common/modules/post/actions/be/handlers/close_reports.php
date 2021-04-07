<?php
$params = $get();

// just make sure they're logged in
$user_id = loggedIn();
if (!$user_id) {
  // well if you anon you don't get EXTRA permissions
  return; // already sends something...
}
//echo "<pre>GET", print_r($_GET, 1), "</pre>\n";
//echo "<pre>POST", print_r($_POST, 1), "</pre>\n";

$target = false;
if (isset($_GET['boardUri'])) {
  $target = 'b/' . $_GET['boardUri'];
}

//echo "user_id[$user_id] target[$target]<br>\n";

// we have to require board (even if from global)

if (!isUserPermitted($user_id, 'close_report', $target)) {
  return sendResponse(array(), 401, 'Access denied');
}

global $db;
$board = $_GET['boardUri'];
$posts_model = getPostsModel($board);

// is _id supposed to be an integer or what? db postid?
// report-{{_id}}
$ids = array();
foreach($_POST as $k => $v) {
  if (substr($k, 0, 6) === 'report') {
    // FIXME; move the post existence check in here...
    $target = substr($k, 7);
    $ids[] = $target;
  }
}
//echo "<pre>IDs [", print_r($ids, 1), "]</pre>\n";
//echo "board[$board]<br>\n";

$removedThreads = 0;
$removedPosts   = 0;

$data = getBoardByUri($board);
$nukes = array();
foreach($data['json']['reports'] as $id => $r) {
  //echo "<pre>r[", print_r($r, 1), "]</pre>\n";
  if ($r['status'] === 'open' && in_array($r['id'], $ids)) {
    //echo "found[$id]<br>\n";
    $post = $db->findById($posts_model, $r['postid']);
    if (!$post) continue;
    // delete single uses GET
    // but I think the mass uses POST
    if ($_REQUEST['deleteContent']) {
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