<?php

function postDBtoAPI(&$row, $post_files_model) {
  global $db, $models;
  $files = array();
  $res = $db->find($post_files_model, array('criteria'=>array(
    array('postid', '=', $row['postid']),
  )));
  while($frow = $db->get_row($res)) {
    $files[] = $frow;
  }
  $row['no'] = $row['postid'];
  $row['files'] = $files;
  unset($row['postid']);
  unset($row['json']);
  // decode user_id
}

function getThread($boardUri, $threadNum) {
  global $db;
  $posts_model = getPostsModel($boardUri);
  $post_files_model = getPostFilesModel($boardUri);
  /*(
  $posts_model['children'] = array(
    array(
      'type' => 'left',
      'model' => $post_files_model,
    )
  );
  */

  $posts = array();
  $res = $db->find($posts_model, array('criteria'=>array(
    array('postid', '=', $threadNum),
  )));
  $row = $db->get_row($res);
  postDBtoAPI($row, $post_files_model);
  $posts[] = $row;

  $res = $db->find($posts_model, array('criteria'=>array(
    array('threadid', '=', $threadNum),
  ), 'order' => 'created_at'));
  while($row = $db->get_row($res)) {
    postDBtoAPI($row, $post_files_model);
    $posts[] = $row;
  }
  return $posts;
}

?>
