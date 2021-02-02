<?php

function postDBtoAPI(&$row) {
  global $db, $models;
  if ($row['deleted'] && $row['deleted'] !== 'f') {
    // non-OPs are automatically hidden...
    $nrow = array(
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
      //'reply_count' => $row['reply_count'],
      //'file_count' => $row['file_count'],
    );
    if (isset($row['reply_count'])) $nrow['reply_count'] = $row['reply_count'];
    if (isset($row['file_count'])) $nrow['file_count'] = $row['file_count'];
    $row = $nrow;
    return;
  }

  // filter out any file_ or post_ field
  $row = array_filter($row, function($v, $k) {
    $f5 = substr($k, 0, 5);
    return $f5 !== 'file_' && $f5 !== 'post_';
  }, ARRAY_FILTER_USE_BOTH);

  $row['no'] = $row['postid'];
  unset($row['postid']);
  unset($row['json']);
  // decode user_id
}

function getThread($boardUri, $threadNum) {
  global $db;
  $posts_model = getPostsModel($boardUri);
  $post_files_model = getPostFilesModel($boardUri);

  //$filesFields = array_keys($post_files_model['fields']);
  //$filesFields[] = 'fileid';

  $filesFields = array('postid', 'sha256', 'path', 'browser_type', 'mime_type',
    'type', 'filename', 'size', 'ext', 'w', 'h', 'filedeleted', 'spoiler', 'fileid');


  $posts_model['children'] = array(
    array(
      'type' => 'left',
      'model' => $post_files_model,
      'pluck' => array_map(function ($f) { return 'ALIAS.' . $f . ' as file_' . $f; }, $filesFields)
    )
  );

  // FIXME: only gets first image on OP
  $posts = array();
  $res = $db->find($posts_model, array('criteria'=>array(
    array('postid', '=', $threadNum),
  )));
  $row = $db->get_row($res);
  $db->free($res);
  //echo "<pre>Thread", print_r($row, 1), "</pre>\n";
  postDBtoAPI($row);
  //echo "<pre>4chan", print_r($row, 1), "</pre>\n";
  $posts[$row['no']] = $row;
  $posts[$row['no']]['files'] = array();
  if (!empty($row['file_fileid'])) {
    if (!isset($posts[$row['no']]['files'][$row['file_fileid']])) {
      $frow = $row;
      fileDBtoAPI($frow);
      $posts[$row['no']]['files'][$row['file_fileid']] = $frow;
    }
  }

  $res = $db->find($posts_model, array('criteria'=>array(
    array('threadid', '=', $threadNum),
    array('deleted', '=', 0),
  ), 'order' => 'created_at'));
  while($row = $db->get_row($res)) {
    //echo "<pre>", print_r($row, 1), "</pre>\n";
    $orow = $row;
    if (!isset($posts[$row['postid']])) {
      postDBtoAPI($row);
      $posts[$row['no']] = $row;
      $posts[$row['no']]['files'] = array();
    }
    if (!empty($orow['file_fileid'])) {
      if (!isset($posts[$orow['postid']]['files'][$orow['file_fileid']])) {
        $frow = $orow;
        fileDBtoAPI($frow);
        $posts[$orow['postid']]['files'][$orow['file_fileid']] = $frow;
      }
    }
  }
  foreach($posts as $pk => $p) {
    $posts[$pk]['files'] = array_values($posts[$pk]['files']);
  }
  $posts = array_values($posts);
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
