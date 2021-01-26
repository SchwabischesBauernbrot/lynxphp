<?php

function postDBtoAPI(&$row, $post_files_model) {
  global $db, $models;
  if ($row['deleted'] && $row['deleted'] !== 'f') {
    // non-OPs are automatically hidden...
    $row = array(
      'postid' => $row['postid'],
      'no' => $row['postid'],
      'threadid' => $row['threadid'],
      'deleted' => 1,
      'com' => 'Thread OP has been deleted but this placeholder is kept, so replies can be read',
      'sub' => '',
      'name' => '',
      // no reasons to hide these...
      'created_at' => $row['created_at'],
      'updated_at' => $row['updated_at'],
      'files' => array(),
      // catalog uses this
      'reply_count' => $row['reply_count'],
    );
    return;
  }
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
    array('deleted', '=', 0),
  ), 'order' => 'created_at'));
  while($row = $db->get_row($res)) {
    postDBtoAPI($row, $post_files_model);
    $posts[] = $row;
  }
  return $posts;
}

function deletePost($boardUri, $postid, $options = false, $post = false) {
  global $db;

  // ensure post
  $posts_model = getPostsModel($boardUri);
  if ($post === false) {
    $post = $db->findById($posts_model, $postid);
  }

  // is this a thread...
  if (!$post['threadid']) {
    if ($options['deleteReplies']) {
      // thread deletion request
      //$res = $db->find($posts_model, array('criteria' => array('threadid' => $postid)));
      // FIXME: write me!
    } else {
      // only disable OP
      $post['deleted'] = true;
      if (!$db->updateById($posts_model, $postid, $post)) {
        // FIXME: log error?
        return false;
      }
    }
  } else {
    // try to delete it
    if (!$db->deleteById($posts_model, $postid)) {
      // FIXME: log error?
      return false;
    }
  }

  // check files
  $files_model = getPostFilesModel($boardUri);
  $res = $db->find($files_model, array('criteria' => array('postid' => $postid)));
  $files = $db->toArray($res);
  $db->free($res);

  if (count($files)) {
    $file_ids = array();
    foreach($files as $f) {
      if (file_exists($f['path'])) {
        unlink($f['path']);
      } else {
        $issues[$r['board'].'_'.$r['postid']] = 'file missing: ' . $f['path'];
      }
      $file_ids[]= $f['fileid'];
    }
    if (!$db->delete($files_model, array(
        'criteria' => array(array('fileid', 'in', $file_ids))
      ))) {
      return false;
    }
  }

  return true;
}

?>
