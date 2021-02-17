<?php
$params = $get();

global $db;

$result = array();
foreach($_POST as $board => $json) {
  // load board
  $posts_model = getPostsModel($board);
  $threads = array();
  $bt = json_decode($json, true);
  foreach($bt as $postid => $t) {
    $row = $db->findById($posts_model, $postid);
    $threads[$postid] = $row['threadid'] ? $row['threadid'] : $postid;
  }
  $result[$board] = $threads;
}

sendResponse($result);

?>