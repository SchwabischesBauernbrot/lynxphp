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

  $row['no'] = empty($row['postid']) ? 0 : $row['postid'];
  unset($row['postid']);

  $data = empty($row['json']) ? array() : json_decode($row['json'], true);

  // FIXME: pipeline
  if (empty($data['cyclic'])) {
    // is this even needed
    unset($row['cyclic']);
  } else {
    $row['cyclic'] = 1;
  }

  unset($row['json']);
  // ensure frontend doesn't have to worry about database differences
  $bools = array('deleted', 'sticky', 'closed');
  foreach($bools as $f) {
    // follow 4chan spec
    if ($db->isTrue($row[$f])) {
      $row[$f] = 1;
    } else {
      unset($row[$f]);
    }
  }
  // decode user_id
}

function getPost($boardUri, $postNo, $posts_model) {
  global $db;
  if ($posts_model === false) {
    $posts_model = getPostsModel($boardUri);
    if ($posts_model === false) {
      // this board does not exist
      sendResponse(array(), 404, 'Board not found');
      return;
    }
  }
  $post_files_model = getPostFilesModel($boardUri);

  //$filesFields = array_keys($post_files_model['fields']);
  //$filesFields[] = 'fileid';

  $filesFields = array('postid', 'sha256', 'path', 'browser_type', 'mime_type',
    'type', 'filename', 'size', 'ext', 'w', 'h', 'filedeleted', 'spoiler',
    'tn_w', 'tn_h', 'fileid');

  // get OP
  // FIXME: only gets first image on OP
  // why? /:board/thread/:thread uses this too
  /*
  $res = $db->find($posts_model, array('criteria'=>array(
    array('postid', '=', $postNo),
    array('deleted', '=', 0),
  )));
  $row = $db->get_row($res);
  */
  $posts = array();
  $row = $db->findById($posts_model, $postNo);
  //echo "<pre>Thread/File", print_r($row, 1), "</pre>\n";
  if ($db->isTrue($row['deleted'])) return array();
  if (!isset($posts[$row['postid']])) {
    //echo "<pre>Thread", print_r($row, 1), "</pre>\n";
    $posts[$row['postid']] = $row;
    postDBtoAPI($posts[$row['postid']]);
    //echo "<pre>4chan", print_r($row, 1), "</pre>\n";
    $posts[$row['postid']]['files'] = array();
  }
  if (!empty($row['file_fileid'])) {
    if (!isset($posts[$row['postid']]['files'][$row['file_fileid']])) {
      $frow = $row;
      fileDBtoAPI($frow, $boardUri);
      $posts[$row['postid']]['files'][$row['file_fileid']] = $frow;
    }
  }
  //$db->free($res);

  /*
  $res = $db->find($posts_model, array('criteria'=>array(
    array('threadid', '=', $postNo),
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
        fileDBtoAPI($frow, $boardUri);
        $posts[$orow['postid']]['files'][$orow['file_fileid']] = $frow;
      }
    }
  }
  */
  foreach($posts as $pk => $p) {
    $posts[$pk]['files'] = array_values($posts[$pk]['files']);
  }
  $posts = array_values($posts);
  $post = count($posts) ? $posts[0] : array();
  return $post;
}

// option.deleteReplies:bool
function deletePost($boardUri, $postid, $options = false, $post = false) {
  global $db, $now, $models;

  // ensure post
  $posts_model = getPostsModel($boardUri);
  if ($posts_model === false) {
    $post = $db->findById($posts_model, $postid);
  }

  $inow = (int)$now;
  $urow = array('last_post' => $inow);

  // is this a thread...
  if (!$post['threadid']) {
    $urow['last_thread'] = $inow;
    if ($options && $options['deleteReplies']) {
      // thread deletion request
      //$res = $db->find($posts_model, array('criteria' => array('threadid' => $postid)));
      // FIXME: write me!
      echo "Write me";
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

  // communicate it to the caches
  $db->update($models['board'], $urow, array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));

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